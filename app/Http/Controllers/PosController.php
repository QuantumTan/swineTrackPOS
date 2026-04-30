<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function sales(): View
    {
        $catalogItems = Product::query()
            ->with('category')
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.*', 'inventory.current_stock', 'inventory.last_updated_at')
            ->orderBy('product.product_name')
            ->get();

        return view('pos.sales', [
            'terminalMeta' => [
                'title' => 'SALES MODE - Active Transaction',
                'subtitle' => 'Point of Sale Terminal',
                'time' => now()->format('h:i:s A'),
                'date' => now()->format('M d, Y'),
            ],
            'catalogItems' => $catalogItems,
            'cashShortcuts' => [
                100,
                200,
                500,
                1000,
            ],
            'numberKeys' => [
                '7', '8', '9',
                '4', '5', '6',
                '1', '2', '3',
                '00', '0', 'CLR',
            ],
        ]);
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:product,product_id'],
            'items.*.qty_sold_kg' => ['required', 'numeric', 'gt:0'],
            'cash_received' => ['required', 'numeric', 'gte:0'],
        ]);

        $items = collect($validated['items'])
            ->groupBy('product_id')
            ->map(fn ($group, $productId): array => [
                'product_id' => (int) $productId,
                'qty_sold_kg' => (float) $group->sum('qty_sold_kg'),
            ])
            ->values();

        if ($stockError = $this->firstInsufficientStockError($items)) {
            return back()
                ->withInput()
                ->withErrors(['items' => $stockError])
                ->with('error', $stockError);
        }

        $batch = $this->findBatchForSale($items);

        if (! $batch) {
            return back()
                ->withInput()
                ->with('error', 'No stock-in batch has enough remaining quantity for every cart item.');
        }

        try {
            $saleId = DB::transaction(function () use ($items, $batch, $request, $validated): int {
                $products = Product::query()
                    ->whereIn('product.product_id', $items->pluck('product_id'))
                    ->get()
                    ->keyBy('product_id');

                $inventory = DB::table('inventory')
                    ->whereIn('product_id', $items->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                $saleItemInsertDeductsInventory = $this->saleItemInsertDeductsInventory();
                $subtotal = 0;

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    $stock = (float) ($inventory->get($item['product_id'])?->current_stock ?? 0);

                    if (! $product || $stock < $item['qty_sold_kg']) {
                        throw new \RuntimeException('Insufficient stock for one or more cart items.');
                    }

                    $subtotal += $item['qty_sold_kg'] * (float) $product->product_price_per_kilo;
                }

                if ((float) $validated['cash_received'] < $subtotal) {
                    throw new \RuntimeException('Cash received is lower than the total amount.');
                }

                if (! $this->saleItemInsertDeductsBatchItems()) {
                    foreach ($items as $item) {
                        $updated = DB::table('batch_item')
                            ->where('batch_id', $batch->batch_id)
                            ->where('product_id', $item['product_id'])
                            ->where('qty_in_kg', '>=', $item['qty_sold_kg'])
                            ->update([
                                'qty_in_kg' => DB::raw('qty_in_kg - '.number_format((float) $item['qty_sold_kg'], 3, '.', '')),
                            ]);

                        if ($updated !== 1) {
                            throw new \RuntimeException('Insufficient batch stock for one or more cart items.');
                        }
                    }
                }

                $saleId = DB::table('sale')->insertGetId([
                    'batch_id' => $batch->batch_id,
                    'user_id' => $request->user()->user_id,
                    'sale_date' => now(),
                ], 'sale_id');

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);

                    if (! $saleItemInsertDeductsInventory) {
                        $updated = DB::table('inventory')
                            ->where('product_id', $item['product_id'])
                            ->where('current_stock', '>=', $item['qty_sold_kg'])
                            ->update([
                                'current_stock' => DB::raw('current_stock - '.number_format((float) $item['qty_sold_kg'], 3, '.', '')),
                                'last_updated_at' => now(),
                            ]);

                        if ($updated !== 1) {
                            throw new \RuntimeException('Insufficient stock for one or more cart items.');
                        }
                    }

                    DB::table('sale_item')->insert([
                        'sale_id' => $saleId,
                        'product_id' => $item['product_id'],
                        'qty_sold_kg' => $item['qty_sold_kg'],
                        'price_per_kg' => $product->product_price_per_kilo,
                    ]);
                }

                DB::table('payment')->insert([
                    'sale_id' => $saleId,
                    'amount' => $subtotal,
                    'payment_status' => 'paid',
                    'payment_date' => now(),
                ]);

                return $saleId;
            });
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale #'.$saleId.' completed successfully.')
            ->with('receipt', $this->buildReceipt($saleId, (float) $validated['cash_received']));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{product_id: int, qty_sold_kg: float}>  $items
     */
    private function firstInsufficientStockError($items): ?string
    {
        $products = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->whereIn('product.product_id', $items->pluck('product_id'))
            ->select('product.product_id', 'product.product_name', 'inventory.current_stock')
            ->get()
            ->keyBy('product_id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $availableStock = (float) ($product?->current_stock ?? 0);
            $requestedQty = (float) $item['qty_sold_kg'];

            if ($requestedQty > $availableStock) {
                $productName = $product?->product_name ?? 'Selected product';

                return sprintf(
                    '%s only has %s kg available. Requested quantity was %s kg.',
                    $productName,
                    number_format($availableStock, 3),
                    number_format($requestedQty, 3)
                );
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{product_id: int, qty_sold_kg: float}>  $items
     */
    private function findBatchForSale($items): ?Batch
    {
        $query = Batch::query();

        foreach ($items as $item) {
            $query->whereHas('items', fn ($batchItems) => $batchItems
                ->where('product_id', $item['product_id'])
                ->where('qty_in_kg', '>=', $item['qty_sold_kg']));
        }

        return $query
            ->orderByRaw("CASE WHEN batch_status = 'Closed' THEN 1 ELSE 0 END")
            ->latest('batch_date')
            ->latest('batch_id')
            ->first();
    }

    private function saleItemInsertDeductsInventory(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', 'trg_sale_item_after_insert')
            ->exists();
    }

    private function saleItemInsertDeductsBatchItems(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', 'trg_sale_item_after_insert')
            ->where('ACTION_STATEMENT', 'like', '%batch_item%')
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReceipt(int $saleId, float $cashReceived): array
    {
        $sale = DB::table('sale')
            ->join('user', 'user.user_id', '=', 'sale.user_id')
            ->where('sale.sale_id', $saleId)
            ->select('sale.sale_id', 'sale.sale_date', 'sale.batch_id', 'user.user_email')
            ->first();

        $items = DB::table('sale_item')
            ->join('product', 'product.product_id', '=', 'sale_item.product_id')
            ->where('sale_item.sale_id', $saleId)
            ->select(
                'product.product_name',
                'sale_item.qty_sold_kg',
                'sale_item.price_per_kg',
                DB::raw('(sale_item.qty_sold_kg * sale_item.price_per_kg) as line_total')
            )
            ->orderBy('sale_item.sale_item_id')
            ->get();

        $total = (float) DB::table('payment')
            ->where('sale_id', $saleId)
            ->value('amount');

        return [
            'sale_id' => $saleId,
            'sale_date' => optional($sale?->sale_date ? \Carbon\Carbon::parse($sale->sale_date) : null)->format('M d, Y h:i A'),
            'batch_id' => $sale?->batch_id,
            'cashier' => $sale?->user_email,
            'items' => $items->map(fn (object $item): array => [
                'product_name' => $item->product_name,
                'qty_sold_kg' => number_format((float) $item->qty_sold_kg, 3),
                'price_per_kg' => 'P'.number_format((float) $item->price_per_kg, 2),
                'line_total' => 'P'.number_format((float) $item->line_total, 2),
            ])->all(),
            'total' => 'P'.number_format($total, 2),
            'cash_received' => 'P'.number_format($cashReceived, 2),
            'change' => 'P'.number_format(max($cashReceived - $total, 0), 2),
        ];
    }

    public function stockIns(): View
    {
        return view('pos.stock-ins', [
            'stockIns' => [],
            'summary' => [],
            'stockInProducts' => [
                'Pork Chop',
                'Pork Belly (Liempo)',
                'Ground Pork',
                'Pork Ribs',
                'Pork Shoulder (Kasim)',
                'Pork Loin',
            ],
            'suppliers' => [],
        ]);
    }

    public function products(): View
    {
        return view('pos.products', [
            'products' => [],
            'categories' => [
                'Premium Cuts',
                'Ground Meat',
                'Standard Cuts',
                'Offal',
            ],
        ]);
    }

    public function inventory(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category_id' => (string) $request->query('category_id', ''),
            'stock_status' => (string) $request->query('stock_status', ''),
        ];

        $latestSupplierSubquery = BatchItem::query()
            ->join('batch', 'batch.batch_id', '=', 'batch_item.batch_id')
            ->leftJoin('supplier', 'supplier.supplier_id', '=', 'batch.supplier_id')
            ->whereColumn('batch_item.product_id', 'product.product_id')
            ->orderByDesc('batch.batch_date')
            ->orderByDesc('batch.batch_id')
            ->selectRaw("
                CASE
                    WHEN batch.source_type = 'Own Livestock' THEN 'Own Livestock'
                    ELSE COALESCE(supplier.supplier_name, 'N/A')
                END
            ")
            ->limit(1);

        $inventoryRows = Product::query()
            ->with('category')
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.*', 'inventory.current_stock', 'inventory.last_updated_at')
            ->selectSub($latestSupplierSubquery, 'latest_supplier')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($searchQuery) use ($filters) {
                    $searchQuery
                        ->where('product.product_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('product.product_id', (int) $filters['search'])
                        ->orWhereExists(function ($supplierQuery) use ($filters) {
                            $supplierQuery
                                ->select(DB::raw(1))
                                ->from('batch_item')
                                ->join('batch', 'batch.batch_id', '=', 'batch_item.batch_id')
                                ->leftJoin('supplier', 'supplier.supplier_id', '=', 'batch.supplier_id')
                                ->whereColumn('batch_item.product_id', 'product.product_id')
                                ->where('supplier.supplier_name', 'like', '%'.$filters['search'].'%');
                        });
                });
            })
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('product.category_id', $filters['category_id']))
            ->when($filters['stock_status'] !== '', function ($query) use ($filters) {
                if ($filters['stock_status'] === 'in_stock') {
                    $query->where('inventory.current_stock', '>=', Product::LOW_STOCK_THRESHOLD);
                } elseif ($filters['stock_status'] === 'low_stock') {
                    $query->where('inventory.current_stock', '>', 0)
                        ->where('inventory.current_stock', '<', Product::LOW_STOCK_THRESHOLD);
                } elseif ($filters['stock_status'] === 'out_of_stock') {
                    $query->where(function ($stockQuery) {
                        $stockQuery->whereNull('inventory.current_stock')
                            ->orWhere('inventory.current_stock', '<=', 0);
                    });
                }
            })
            ->orderBy('product.product_id')
            ->paginate(10)
            ->withQueryString();

        $allInventoryRows = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.product_id', 'inventory.current_stock')
            ->get();

        return view('pos.inventory', [
            'inventoryItems' => $inventoryRows,
            'filters' => $filters,
            'categories' => Category::query()->orderBy('category_name')->get(),
            'summary' => [
                [
                    'label' => 'Total Products',
                    'value' => $allInventoryRows->count(),
                ],
                [
                    'label' => 'In Stock',
                    'value' => $allInventoryRows->filter(fn ($row) => (float) ($row->current_stock ?? 0) > 0)->count(),
                ],
                [
                    'label' => 'Low Stock / Out',
                    'value' => $allInventoryRows->filter(
                        fn ($row) => (float) ($row->current_stock ?? 0) < Product::LOW_STOCK_THRESHOLD
                    )->count(),
                ],
            ],
        ]);
    }
}

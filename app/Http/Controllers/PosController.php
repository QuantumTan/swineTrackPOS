<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function dashboard(): View
    {
        $dailySalesSummary = $this->staticDailySalesSummary();
        $lowStockProducts = $this->staticLowStockProducts();
        $inventorySnapshot = $this->staticInventorySnapshot();
        $batchDetails = $this->staticBatchDetails();
        $triggerSummary = $this->staticTriggerSummary();

        return view('dashboard', [
            'summaryCards' => [
                [
                    'label' => 'Sales Summary',
                    'value' => 'P155,480.00',
                    'trend' => '4-day sales total',
                    'icon' => 'bi-graph-up-arrow',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => '4',
                    'trend' => 'Products that need attention',
                    'icon' => 'bi-exclamation-triangle',
                ],
                [
                    'label' => 'Inventory Snapshot',
                    'value' => '4',
                    'trend' => 'Items shown in the stock preview',
                    'icon' => 'bi-box-seam',
                ],
                [
                    'label' => 'Starting Stock',
                    'value' => '0 kg',
                    'trend' => 'New products begin at zero stock',
                    'icon' => 'bi-lightning-charge',
                ],
            ],
            'viewTags' => [
                'vw_product_inventory',
                'vw_low_stock_products',
                'vw_batch_details',
                'vw_sales_details',
                'vw_daily_sales_summary',
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'inventorySnapshot' => $inventorySnapshot,
            'batchDetails' => $batchDetails,
            'triggerSummary' => $triggerSummary,
            'coverageItems' => $this->staticCoverageItems(),
            'salesGraph' => [
                [
                    'label' => 'Apr 17',
                    'total_sales' => 'P30,925.00',
                    'transactions' => 14,
                    'height' => 65,
                ],
                [
                    'label' => 'Apr 18',
                    'total_sales' => 'P34,480.00',
                    'transactions' => 16,
                    'height' => 73,
                ],
                [
                    'label' => 'Apr 19',
                    'total_sales' => 'P47,215.00',
                    'transactions' => 21,
                    'height' => 100,
                ],
                [
                    'label' => 'Apr 20',
                    'total_sales' => 'P42,860.00',
                    'transactions' => 18,
                    'height' => 91,
                ],
            ],
            'inventoryStatusGraph' => [
                [
                    'label' => 'In Stock',
                    'count' => 2,
                    'width' => 50,
                    'type' => 'success',
                ],
                [
                    'label' => 'Low Stock',
                    'count' => 1,
                    'width' => 25,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Out of Stock',
                    'count' => 1,
                    'width' => 25,
                    'type' => 'danger',
                ],
            ],
            'batchCostGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P3,690.00',
                    'width' => 79,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P2,275.00',
                    'width' => 49,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P4,656.00',
                    'width' => 100,
                    'type' => 'success',
                ],
            ],
            'lowStockGraph' => [
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 87,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Ribs',
                    'value' => '4.250 kg',
                    'width' => 44,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 8,
                    'type' => 'danger',
                ],
                [
                    'label' => 'Pork Loin',
                    'value' => '9.750 kg',
                    'width' => 98,
                    'type' => 'warning',
                ],
            ],
        ]);
    }

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

        $batch = Batch::query()
            ->whereIn('batch_status', ['Open', 'Sold Out'])
            ->latest('batch_date')
            ->latest('batch_id')
            ->first();

        if (! $batch) {
            return back()
                ->withInput()
                ->with('error', 'Please create a stock-in batch before completing POS sales.');
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

    public function inventory(): View
    {
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
            ->orderBy('product.product_id')
            ->paginate(10)
            ->withQueryString();

        $allInventoryRows = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.product_id', 'inventory.current_stock')
            ->get();

        return view('pos.inventory', [
            'inventoryItems' => $inventoryRows,
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

    public function reports(): View
    {
        $dailySalesSummary = $this->staticDailySalesSummary();
        $lowStockProducts = $this->staticLowStockProducts();
        $batchDetails = $this->staticBatchDetails();
        $salesDetails = $this->staticSalesDetails();
        $inventorySnapshot = $this->staticInventorySnapshot();

        return view('pos.reports', [
            'summaryCards' => [
                [
                    'label' => 'Sales Summary',
                    'value' => 'P155,480.00',
                    'trend' => 'Combined sales across the sample days',
                    'icon' => 'bi-file-earmark-bar-graph',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => '4',
                    'trend' => 'Products currently at or below the warning level',
                    'icon' => 'bi-bell',
                ],
                [
                    'label' => 'Batch Costs',
                    'value' => 'P10,621.00',
                    'trend' => 'Total sample intake cost shown below',
                    'icon' => 'bi-clipboard-data',
                ],
                [
                    'label' => 'Starting Stock',
                    'value' => '0 kg',
                    'trend' => 'New products begin at zero stock',
                    'icon' => 'bi-lightning-charge',
                ],
            ],
            'viewTags' => [
                'vw_daily_sales_summary',
                'vw_low_stock_products',
                'vw_product_inventory',
                'vw_batch_details',
                'vw_sales_details',
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'batchDetails' => $batchDetails,
            'salesDetails' => $salesDetails,
            'inventorySnapshot' => $inventorySnapshot,
            'coverageItems' => $this->staticCoverageItems(),
            'triggerSummary' => $this->staticTriggerSummary(),
            'salesGraph' => [
                [
                    'label' => 'Apr 17',
                    'total_sales' => 'P30,925.00',
                    'transactions' => 14,
                    'height' => 65,
                ],
                [
                    'label' => 'Apr 18',
                    'total_sales' => 'P34,480.00',
                    'transactions' => 16,
                    'height' => 73,
                ],
                [
                    'label' => 'Apr 19',
                    'total_sales' => 'P47,215.00',
                    'transactions' => 21,
                    'height' => 100,
                ],
                [
                    'label' => 'Apr 20',
                    'total_sales' => 'P42,860.00',
                    'transactions' => 18,
                    'height' => 91,
                ],
            ],
            'lowStockGraph' => [
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 87,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Ribs',
                    'value' => '4.250 kg',
                    'width' => 44,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 8,
                    'type' => 'danger',
                ],
                [
                    'label' => 'Pork Loin',
                    'value' => '9.750 kg',
                    'width' => 98,
                    'type' => 'warning',
                ],
            ],
            'inventoryGraph' => [
                [
                    'label' => 'Pork Chop',
                    'value' => '28.000 kg',
                    'width' => 100,
                    'type' => 'success',
                ],
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => '16.500 kg',
                    'width' => 59,
                    'type' => 'success',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 30,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 6,
                    'type' => 'danger',
                ],
            ],
            'batchCostGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P3,690.00',
                    'width' => 79,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P2,275.00',
                    'width' => 49,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P4,656.00',
                    'width' => 100,
                    'type' => 'success',
                ],
            ],
            'salesMixGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P1,567.50',
                    'width' => 100,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P446.25',
                    'width' => 29,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P630.00',
                    'width' => 40,
                    'type' => 'success',
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticCoverageItems(): array
    {
        return [
            [
                'kind' => 'View',
                'name' => 'vw_product_inventory',
                'description' => 'Inventory display for products, stock, price per kilo, update time, and computed stock status.',
                'detail' => 'product_id, product_name, category_name, product_price_per_kilo, current_stock, last_updated_at, stock_status',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_batch_details',
                'description' => 'Batch intake lines covering source, supplier, operator, quantities, and total cost.',
                'detail' => 'batch_id, batch_date, source_type, batch_status, supplier_name, user_email, batch_item_id, product_name, qty_in_kg, cost_per_kg, line_total_cost',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_sales_details',
                'description' => 'Sale line ledger for cashier, batch, product, sold quantity, price, and line total.',
                'detail' => 'sale_id, sale_date, batch_id, user_email, sale_item_id, product_name, qty_sold_kg, price_per_kg, line_total',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_daily_sales_summary',
                'description' => 'Day-level total transactions and total sales used for the sales trend graphs.',
                'detail' => 'sale_day, total_transactions, total_sales',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_low_stock_products',
                'description' => 'Products at or below the low-stock threshold for watchlists and reorder prompts.',
                'detail' => 'product_id, product_name, current_stock',
            ],
            [
                'kind' => 'Trigger',
                'name' => 'trg_product_after_insert',
                'description' => 'Creates the starting inventory row the moment a product is inserted.',
                'detail' => 'AFTER INSERT ON product -> inventory(product_id, current_stock = 0, last_updated_at = NOW())',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticDailySalesSummary(): array
    {
        return [
            [
                'sale_day' => '20 Apr 2026',
                'total_transactions' => 18,
                'total_sales' => 'P42,860.00',
            ],
            [
                'sale_day' => '19 Apr 2026',
                'total_transactions' => 21,
                'total_sales' => 'P47,215.00',
            ],
            [
                'sale_day' => '18 Apr 2026',
                'total_transactions' => 16,
                'total_sales' => 'P34,480.00',
            ],
            [
                'sale_day' => '17 Apr 2026',
                'total_transactions' => 14,
                'total_sales' => 'P30,925.00',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticLowStockProducts(): array
    {
        return [
            [
                'product_id' => 'P003',
                'product_name' => 'Ground Pork',
                'current_stock' => '8.500 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P004',
                'product_name' => 'Pork Ribs',
                'current_stock' => '4.250 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P005',
                'product_name' => 'Pork Shoulder (Kasim)',
                'current_stock' => '0.000 kg',
                'status' => ['label' => 'Out of Stock', 'class' => 'danger'],
            ],
            [
                'product_id' => 'P006',
                'product_name' => 'Pork Loin',
                'current_stock' => '9.750 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticInventorySnapshot(): array
    {
        return [
            [
                'product_id' => 'P001',
                'product_name' => 'Pork Chop',
                'category_name' => 'Premium Cuts',
                'product_price_per_kilo' => 'P315.00',
                'current_stock' => '28.000 kg',
                'last_updated_at' => '20 Apr 2026, 06:30 AM',
                'stock_status' => ['label' => 'In Stock', 'class' => 'success'],
            ],
            [
                'product_id' => 'P002',
                'product_name' => 'Pork Belly (Liempo)',
                'category_name' => 'Premium Cuts',
                'product_price_per_kilo' => 'P330.00',
                'current_stock' => '16.500 kg',
                'last_updated_at' => '20 Apr 2026, 06:15 AM',
                'stock_status' => ['label' => 'In Stock', 'class' => 'success'],
            ],
            [
                'product_id' => 'P003',
                'product_name' => 'Ground Pork',
                'category_name' => 'Ground Meat',
                'product_price_per_kilo' => 'P255.00',
                'current_stock' => '8.500 kg',
                'last_updated_at' => '20 Apr 2026, 05:55 AM',
                'stock_status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P005',
                'product_name' => 'Pork Shoulder (Kasim)',
                'category_name' => 'Standard Cuts',
                'product_price_per_kilo' => 'P285.00',
                'current_stock' => '0.000 kg',
                'last_updated_at' => '20 Apr 2026, 05:40 AM',
                'stock_status' => ['label' => 'Out of Stock', 'class' => 'danger'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticBatchDetails(): array
    {
        return [
            [
                'batch_id' => 'B018',
                'batch_date' => '20 Apr 2026, 04:30 AM',
                'source_type' => 'Supplier',
                'batch_status' => ['label' => 'Open', 'class' => 'primary'],
                'supplier_name' => 'Metro Cuts Trading',
                'user_email' => 'cashier@swine-track.test',
                'batch_item_id' => 'BI053',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_in_kg' => '18.000 kg',
                'cost_per_kg' => 'P205.00',
                'line_total_cost' => 'P3,690.00',
            ],
            [
                'batch_id' => 'B018',
                'batch_date' => '20 Apr 2026, 04:30 AM',
                'source_type' => 'Supplier',
                'batch_status' => ['label' => 'Open', 'class' => 'primary'],
                'supplier_name' => 'Metro Cuts Trading',
                'user_email' => 'cashier@swine-track.test',
                'batch_item_id' => 'BI054',
                'product_name' => 'Ground Pork',
                'qty_in_kg' => '12.500 kg',
                'cost_per_kg' => 'P182.00',
                'line_total_cost' => 'P2,275.00',
            ],
            [
                'batch_id' => 'B017',
                'batch_date' => '19 Apr 2026, 05:10 AM',
                'source_type' => 'Own Livestock',
                'batch_status' => ['label' => 'Closed', 'class' => 'neutral'],
                'supplier_name' => 'Own Livestock',
                'user_email' => 'admin@swine-track.test',
                'batch_item_id' => 'BI052',
                'product_name' => 'Pork Chop',
                'qty_in_kg' => '24.000 kg',
                'cost_per_kg' => 'P194.00',
                'line_total_cost' => 'P4,656.00',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticSalesDetails(): array
    {
        return [
            [
                'sale_id' => 'S041',
                'sale_date' => '20 Apr 2026, 09:15 AM',
                'batch_id' => 'B018',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI118',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_sold_kg' => '3.250 kg',
                'price_per_kg' => 'P330.00',
                'line_total' => 'P1,072.50',
            ],
            [
                'sale_id' => 'S042',
                'sale_date' => '20 Apr 2026, 09:42 AM',
                'batch_id' => 'B018',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI119',
                'product_name' => 'Ground Pork',
                'qty_sold_kg' => '1.750 kg',
                'price_per_kg' => 'P255.00',
                'line_total' => 'P446.25',
            ],
            [
                'sale_id' => 'S043',
                'sale_date' => '20 Apr 2026, 10:05 AM',
                'batch_id' => 'B017',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI120',
                'product_name' => 'Pork Chop',
                'qty_sold_kg' => '2.000 kg',
                'price_per_kg' => 'P315.00',
                'line_total' => 'P630.00',
            ],
            [
                'sale_id' => 'S044',
                'sale_date' => '20 Apr 2026, 10:22 AM',
                'batch_id' => 'B018',
                'user_email' => 'assistant@swine-track.test',
                'sale_item_id' => 'SI121',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_sold_kg' => '1.500 kg',
                'price_per_kg' => 'P330.00',
                'line_total' => 'P495.00',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function staticTriggerSummary(): array
    {
        return [
            'name' => 'trg_product_after_insert',
            'title' => 'Inventory row auto-setup',
            'description' => 'Every new product immediately gets an inventory record with zero stock and the current timestamp.',
        ];
    }
}

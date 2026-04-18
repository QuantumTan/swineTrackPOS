<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockIn\StoreStockInRequest;
use App\Http\Requests\StockIn\UpdateStockInRequest;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(): View
    {
        $batches = Batch::query()
            ->with(['supplier', 'items.product'])
            ->orderByDesc('batch_date')
            ->orderByDesc('batch_id')
            ->paginate(10)
            ->withQueryString();

        $stockIns = $batches->through(function (Batch $batch) {
            $items = $batch->items->map(function (BatchItem $item) {
                return [
                    'batch_item_id' => $item->batch_item_id,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->product_name ?? 'Unknown Product',
                    'qty' => (float) $item->qty_in_kg,
                    'cost' => (float) $item->cost_per_kg,
                    'line_total' => (float) $item->qty_in_kg * (float) $item->cost_per_kg,
                ];
            });

            $totalCost = $items->sum('line_total');

            return [
                'batch_id' => $batch->batch_id,
                'id' => 'B'.str_pad((string) $batch->batch_id, 4, '0', STR_PAD_LEFT),
                'date_value' => $batch->batch_date,
                'date' => Carbon::parse($batch->batch_date)->format('d M Y, h:i A'),
                'source' => [
                    'label' => $batch->source_type,
                    'class' => $batch->source_type === 'Supplier' ? 'success' : 'info',
                ],
                'status' => [
                    'label' => $batch->batch_status,
                    'class' => $batch->batch_status === 'Open' ? 'warning' : ($batch->batch_status === 'Closed' ? 'secondary' : 'success'),
                ],
                'supplier_id' => $batch->supplier_id,
                'supplier' => $batch->supplier?->supplier_name ?? 'N/A',
                'items' => $items,
                'primary_item' => $items->first(),
                'total_value' => $totalCost,
                'total' => 'P'.number_format($totalCost, 2),
            ];
        });

        $todayEntries = Batch::query()
            ->where('batch_date', '>=', now()->startOfDay())
            ->count();

        $weekCost = (float) DB::table('batches')
            ->join('batch_item', 'batch_item.batch_id', '=', 'batches.batch_id')
            ->where('batches.batch_date', '>=', now()->startOfWeek())
            ->selectRaw('SUM(batch_item.qty_in_kg * batch_item.cost_per_kg) as total_cost')
            ->value('total_cost');

        $primarySource = Batch::query()
            ->select('source_type', DB::raw('COUNT(*) as total'))
            ->groupBy('source_type')
            ->orderByDesc('total')
            ->value('source_type') ?? 'N/A';

        return view('pos.stock-ins', [
            'stockIns' => $stockIns,
            'summary' => [
                [
                    'label' => 'Today Entries',
                    'value' => $todayEntries,
                ],
                [
                    'label' => 'This Week Cost',
                    'value' => 'P'.number_format($weekCost, 2),
                ],
                [
                    'label' => 'Primary Source',
                    'value' => $primarySource,
                ],
            ],
            'products' => Product::query()->orderBy('product_name')->get(),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
        ]);
    }

    public function store(StoreStockInRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $batch = Batch::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'user_id' => $request->user()->getKey(),
                'batch_date' => Carbon::parse($validated['batch_date']),
                'source_type' => $validated['source_type'],
                'batch_status' => $validated['batch_status'],
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = BatchItem::create([
                    'batch_id' => $batch->batch_id,
                    'product_id' => $itemData['product_id'],
                    'qty_in_kg' => $itemData['qty_in_kg'],
                    'cost_per_kg' => $itemData['cost_per_kg'],
                ]);

                $this->applyInventoryDelta((int) $item->product_id, (float) $item->qty_in_kg);
            }
        });

        return redirect()
            ->route('stock-ins.index')
            ->with('status', 'Stock-in recorded successfully.');
    }

    public function update(UpdateStockInRequest $request, Batch $batch): RedirectResponse
    {
        DB::transaction(function () use ($request, $batch) {
            $validated = $request->validated();

            $originalItems = $batch->items()
                ->select('product_id', 'qty_in_kg')
                ->get();

            $originalByProduct = [];
            foreach ($originalItems as $item) {
                $productId = (int) $item->product_id;
                $originalByProduct[$productId] = ($originalByProduct[$productId] ?? 0) + (float) $item->qty_in_kg;
            }

            $batch->update([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'batch_date' => Carbon::parse($validated['batch_date']),
                'source_type' => $validated['source_type'],
                'batch_status' => $validated['batch_status'],
            ]);

            $batch->items()->delete();

            $newByProduct = [];
            foreach ($validated['items'] as $itemData) {
                BatchItem::create([
                    'batch_id' => $batch->batch_id,
                    'product_id' => $itemData['product_id'],
                    'qty_in_kg' => $itemData['qty_in_kg'],
                    'cost_per_kg' => $itemData['cost_per_kg'],
                ]);

                $productId = (int) $itemData['product_id'];
                $newByProduct[$productId] = ($newByProduct[$productId] ?? 0) + (float) $itemData['qty_in_kg'];
            }

            $allProductIds = array_unique(array_merge(array_keys($originalByProduct), array_keys($newByProduct)));

            foreach ($allProductIds as $productId) {
                $originalQty = $originalByProduct[$productId] ?? 0;
                $newQty = $newByProduct[$productId] ?? 0;
                $delta = $newQty - $originalQty;

                if ($delta != 0.0) {
                    $this->applyInventoryDelta((int) $productId, $delta);
                }
            }
        });

        return redirect()
            ->route('stock-ins.index')
            ->with('status', 'Stock-in updated successfully.');
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        try {
            DB::transaction(function () use ($batch) {
                $items = $batch->items()->get();

                foreach ($items as $item) {
                    $this->applyInventoryDelta((int) $item->product_id, (float) $item->qty_in_kg * -1);
                }

                $batch->delete();
            });
        } catch (QueryException $exception) {
            return redirect()
                ->route('stock-ins.index')
                ->withErrors([
                    'stock_in_delete' => 'This stock-in record cannot be deleted while it is still linked to other records.',
                ]);
        }

        return redirect()
            ->route('stock-ins.index')
            ->with('status', 'Stock-in deleted successfully.');
    }

    private function applyInventoryDelta(int $productId, float $delta): void
    {
        $inventory = Inventory::firstOrNew(['product_id' => $productId]);
        $currentStock = (float) ($inventory->current_stock_kg ?? 0);

        $inventory->product_id = $productId;
        $inventory->current_stock_kg = max(0, round($currentStock + $delta, 3));
        $inventory->last_updated_at = now();
        $inventory->save();
    }
}
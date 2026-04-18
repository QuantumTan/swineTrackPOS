<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockIn\StoreStockInRequest;
use App\Http\Requests\StockIn\UpdateStockInRequest;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockInService;
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

        $stockIns = $batches->through(fn (Batch $batch) => $this->presentBatch($batch));

        return view('pos.stock-ins', [
            'stockIns' => $stockIns,
            'summary' => $this->summary(),
            'products' => Product::query()->orderBy('product_name')->get(),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
        ]);
    }

    public function store(StoreStockInRequest $request, StockInService $stockInService): RedirectResponse
    {
        $stockInService->create($request->validated(), (int) $request->user()->getKey());

        return redirect()
            ->route('stock-ins.index')
            ->with('status', 'Stock-in recorded successfully.');
    }

    public function update(
        UpdateStockInRequest $request,
        Batch $batch,
        StockInService $stockInService
    ): RedirectResponse {
        $stockInService->update($batch, $request->validated());

        return redirect()
            ->route('stock-ins.index')
            ->with('status', 'Stock-in updated successfully.');
    }

    public function destroy(Batch $batch, StockInService $stockInService): RedirectResponse
    {
        try {
            $stockInService->delete($batch);
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

    /**
     * @return array<string, mixed>
     */
    private function presentBatch(Batch $batch): array
    {
        $items = $batch->items->map(fn (BatchItem $item) => $this->presentBatchItem($item));
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
            'supplier_id' => $batch->supplier_id,
            'supplier' => $batch->supplier?->supplier_name ?? 'N/A',
            'items' => $items,
            'primary_item' => $items->first(),
            'total_value' => $totalCost,
            'total' => 'P'.number_format($totalCost, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBatchItem(BatchItem $item): array
    {
        return [
            'batch_item_id' => $item->batch_item_id,
            'product_id' => $item->product_id,
            'name' => $item->product?->product_name ?? 'Unknown Product',
            'qty' => (float) $item->qty_in_kg,
            'cost' => (float) $item->cost_per_kg,
            'line_total' => (float) $item->qty_in_kg * (float) $item->cost_per_kg,
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function summary(): array
    {
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

        return [
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
        ];
    }
}

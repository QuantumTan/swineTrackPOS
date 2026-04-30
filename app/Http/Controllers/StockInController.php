<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockIn\StoreStockInRequest;
use App\Http\Requests\StockIn\UpdateStockInRequest;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockInService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'source_type' => (string) $request->query('source_type', ''),
            'batch_status' => (string) $request->query('batch_status', ''),
        ];

        $batches = Batch::query()
            ->with(['supplier', 'items.product'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($searchQuery) use ($filters) {
                    $searchQuery
                        ->where('batch_id', (int) $filters['search'])
                        ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                            ->where('supplier_name', 'like', '%'.$filters['search'].'%'));
                });
            })
            ->when($filters['source_type'] !== '', fn ($query) => $query->where('source_type', $filters['source_type']))
            ->when($filters['batch_status'] !== '', function ($query) use ($filters) {
                if ($filters['batch_status'] === 'closed') {
                    $query->where('batch_status', 'Closed');
                } elseif ($filters['batch_status'] === 'sold_out') {
                    $query->where('batch_status', '!=', 'Closed')
                        ->whereHas('items')
                        ->whereDoesntHave('items', fn ($itemQuery) => $itemQuery->where('qty_in_kg', '>', 0));
                } elseif ($filters['batch_status'] === 'open') {
                    $query->where('batch_status', '!=', 'Closed')
                        ->where(function ($openQuery) {
                            $openQuery->whereDoesntHave('items')
                                ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('qty_in_kg', '>', 0));
                        });
                }
            })
            ->orderByDesc('batch_date')
            ->orderByDesc('batch_id')
            ->paginate(10)
            ->withQueryString();

        $activeSuppliers = Supplier::query()
            ->where('status', 'Active')
            ->orderBy('supplier_name')
            ->get();

        $selectableSuppliers = $activeSuppliers
            ->concat($batches->getCollection()->pluck('supplier')->filter())
            ->unique('supplier_id')
            ->sortBy('supplier_name')
            ->values();

        return view('pos.stock-ins', [
            'stockIns' => $batches,
            'filters' => $filters,
            'summary' => $this->summary(),
            'products' => Product::query()->orderBy('product_name')->get(),
            'activeSuppliers' => $activeSuppliers,
            'selectableSuppliers' => $selectableSuppliers,
            'canCreateStockIn' => ! $this->hasOpenStockInWithRemainingQuantity(),
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
     * @return array<int, array<string, int|string>>
     */
    private function summary(): array
    {
        $todayEntries = Batch::query()
            ->where('batch_date', '>=', now()->startOfDay())
            ->count();

        $weekCost = (float) DB::table('batch')
            ->join('batch_item', 'batch_item.batch_id', '=', 'batch.batch_id')
            ->where('batch.batch_date', '>=', now()->startOfWeek())
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

    private function hasOpenStockInWithRemainingQuantity(): bool
    {
        return Batch::query()
            ->where('batch_status', '!=', 'Closed')
            ->whereHas('items', fn ($query) => $query->where('qty_in_kg', '>', 0))
            ->exists();
    }
}

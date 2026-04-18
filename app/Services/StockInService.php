<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Inventory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, int $userId): Batch
    {
        return DB::transaction(function () use ($validated, $userId) {
            $this->ensureFirstBatchIsSoldOut();

            $batch = Batch::create($this->batchAttributes($validated, $userId) + [
                'batch_status' => 'Open',
            ]);

            $this->createBatchItems($batch, $validated['items']);
            $this->syncInventory([], $this->quantitiesByProduct($validated['items']));

            return $batch;
        });
    }

    private function ensureFirstBatchIsSoldOut(): void
    {
        $firstBatch = Batch::query()
            ->orderBy('batch_date')
            ->orderBy('batch_id')
            ->first();

        if ($firstBatch !== null && $firstBatch->batch_status !== 'Sold Out') {
            throw ValidationException::withMessages([
                'stock_in_create' => 'Cannot record a new stock-in until the first batch is marked Sold Out.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Batch $batch, array $validated): void
    {
        DB::transaction(function () use ($batch, $validated) {
            $originalQuantities = $this->quantitiesByProduct(
                $batch->items()
                    ->select('product_id', 'qty_in_kg')
                    ->get()
            );

            $batch->update($this->batchAttributes($validated));

            $batch->items()->delete();

            $this->createBatchItems($batch, $validated['items']);
            $this->syncInventory($originalQuantities, $this->quantitiesByProduct($validated['items']));
        });
    }

    public function delete(Batch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $originalQuantities = $this->quantitiesByProduct(
                $batch->items()
                    ->select('product_id', 'qty_in_kg')
                    ->get()
            );

            $this->syncInventory($originalQuantities, []);
            $batch->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function batchAttributes(array $validated, ?int $userId = null): array
    {
        $attributes = [
            'supplier_id' => $validated['supplier_id'] ?? null,
            'batch_date' => Carbon::parse($validated['batch_date']),
            'source_type' => $validated['source_type'],
        ];

        if ($userId !== null) {
            $attributes['user_id'] = $userId;
        }

        return $attributes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createBatchItems(Batch $batch, array $items): void
    {
        $batch->items()->createMany(array_map(
            fn (array $item): array => [
                'product_id' => $item['product_id'],
                'qty_in_kg' => $item['qty_in_kg'],
                'cost_per_kg' => $item['cost_per_kg'],
            ],
            $items
        ));
    }

    /**
     * @param  iterable<int, BatchItem|array<string, mixed>|object>  $items
     * @return array<int, float>
     */
    private function quantitiesByProduct(iterable $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) (is_array($item) ? $item['product_id'] : $item->product_id);
            $quantity = (float) (is_array($item) ? $item['qty_in_kg'] : $item->qty_in_kg);

            $quantities[$productId] = ($quantities[$productId] ?? 0.0) + $quantity;
        }

        return $quantities;
    }

    /**
     * @param  array<int, float>  $originalQuantities
     * @param  array<int, float>  $updatedQuantities
     */
    private function syncInventory(array $originalQuantities, array $updatedQuantities): void
    {
        $productIds = array_unique(array_merge(
            array_keys($originalQuantities),
            array_keys($updatedQuantities)
        ));

        foreach ($productIds as $productId) {
            $delta = ($updatedQuantities[$productId] ?? 0.0) - ($originalQuantities[$productId] ?? 0.0);

            if ($delta !== 0.0) {
                $this->applyInventoryDelta((int) $productId, $delta);
            }
        }
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

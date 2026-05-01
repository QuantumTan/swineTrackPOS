<?php

namespace App\Services;

use App\Enums\BatchStatus;
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
            $this->ensureNoOpenBatchExists();

            $batch = Batch::create($this->batchAttributes($validated, $userId) + [
                'batch_status' => BatchStatus::Open->value,
            ]);

            $this->createBatchItems($batch, $validated['items']);

            if (! $this->batchItemTriggersSyncInventory()) {
                $this->syncInventory([], $this->quantitiesByProduct($validated['items']));
            }

            return $batch;
        });
    }

    private function ensureNoOpenBatchExists(): void
    {
        // Check if there's any batch that is NOT Closed AND has items with remaining quantity
        // Use a raw subquery to ensure the check is precise
        $openBatchExists = Batch::query()
            ->where('batch_status', '!=', BatchStatus::Closed->value)
            ->whereRaw('batch.batch_id IN (SELECT DISTINCT batch_id FROM batch_item WHERE qty_in_kg > 0)')
            ->exists();

        if ($openBatchExists) {
            throw ValidationException::withMessages([
                'stock_in_create' => 'Cannot record a new stock-in while another batch still has remaining quantity. It will become Sold Out automatically at zero, or you can mark it Closed.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Batch $batch, array $validated): void
    {
        DB::transaction(function () use ($batch, $validated) {
            $this->ensureOpenStatusDoesNotConflict($batch, $validated);

            $originalQuantities = $this->quantitiesByProduct(
                $batch->items()
                    ->select('product_id', 'qty_in_kg')
                    ->get()
            );

            $batch->update($this->batchAttributes($validated));

            $batch->items()->delete();

            $this->createBatchItems($batch, $validated['items']);

            if (! $this->batchItemTriggersSyncInventory()) {
                $this->syncInventory($originalQuantities, $this->quantitiesByProduct($validated['items']));
            }
        });
    }

    public function delete(Batch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $batchItemTriggersSyncInventory = $this->batchItemTriggersSyncInventory();

            if (! $batchItemTriggersSyncInventory) {
                $originalQuantities = $this->quantitiesByProduct(
                    $batch->items()
                        ->select('product_id', 'qty_in_kg')
                        ->get()
                );

                $this->syncInventory($originalQuantities, []);
            }

            $batch->items()->delete();
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

        if (array_key_exists('batch_status', $validated)) {
            $attributes['batch_status'] = $validated['batch_status'];
        }

        if ($userId !== null) {
            $attributes['user_id'] = $userId;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureOpenStatusDoesNotConflict(Batch $batch, array $validated): void
    {
        if (($validated['batch_status'] ?? null) === BatchStatus::Closed->value) {
            return;
        }

        if (! $this->hasRemainingQuantity($validated['items'] ?? [])) {
            return;
        }

        $anotherOpenBatchExists = Batch::query()
            ->where('batch_id', '!=', $batch->batch_id)
            ->where('batch_status', '!=', BatchStatus::Closed->value)
            ->whereHas('items', fn ($query) => $query->where('qty_in_kg', '>', 0))
            ->exists();

        if ($anotherOpenBatchExists) {
            throw ValidationException::withMessages([
                'batch_status' => 'Only one batch with remaining quantity can stay Open at a time.',
            ]);
        }
    }

    /**
     * @param  iterable<int, BatchItem|array<string, mixed>|object>  $items
     */
    private function hasRemainingQuantity(iterable $items): bool
    {
        foreach ($items as $item) {
            $quantity = (float) (is_array($item) ? $item['qty_in_kg'] : $item->qty_in_kg);

            if ($quantity > 0) {
                return true;
            }
        }

        return false;
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
        $currentStock = (float) ($inventory->current_stock ?? 0);

        $inventory->product_id = $productId;
        $inventory->current_stock = max(0, round($currentStock + $delta, 3));
        $inventory->last_updated_at = now();
        $inventory->save();
    }

    private function batchItemTriggersSyncInventory(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $installedTriggers = DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->whereIn('TRIGGER_NAME', [
                'trg_batch_item_after_insert',
                'trg_batch_item_after_update',
                'trg_batch_item_after_delete',
            ])
            ->count();

        return $installedTriggers === 3;
    }
}

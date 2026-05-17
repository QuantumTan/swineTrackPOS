<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $table = 'batch';

    protected $primaryKey = 'batch_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'batch_date',
        'source_type',
        'batch_status',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'datetime',
            'batch_status' => BatchStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Batch $batch): void {
            if ($batch->source_type === 'Supplier' && blank($batch->supplier_id)) {
                throw new \InvalidArgumentException('Supplier-sourced batches must be linked to exactly one supplier.');
            }

            if ($batch->source_type === 'Own Livestock' && filled($batch->supplier_id)) {
                throw new \InvalidArgumentException('Own livestock batches must not be linked to a supplier.');
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'batch_id', 'batch_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'batch_id', 'batch_id');
    }

    public function getDisplayIdAttribute(): string
    {
        return 'B'.str_pad((string) $this->batch_id, 4, '0', STR_PAD_LEFT);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->batch_date->format('d M Y, h:i A');
    }

    /**
     * @return array{label: string, class: string}
     */
    public function getSourcePresentationAttribute(): array
    {
        return [
            'label' => $this->source_type,
            'class' => $this->source_type === 'Supplier' ? 'success' : 'info',
        ];
    }

    public function getSupplierDisplayAttribute(): string
    {
        return $this->supplier?->supplier_name ?? 'N/A';
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getTotalQtyValueAttribute(): float
    {
        return (float) $this->items->sum(fn (BatchItem $item): float => $item->qty_value);
    }

    public function getFormattedTotalQtyAttribute(): string
    {
        return number_format($this->total_qty_value, 3).' kg';
    }

    public function getTotalCostValueAttribute(): float
    {
        return (float) $this->items->sum(fn (BatchItem $item): float => $item->line_total_value);
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return 'P'.number_format($this->total_cost_value, 2);
    }
}

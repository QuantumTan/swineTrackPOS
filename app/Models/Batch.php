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

    protected $table = 'batches';

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

    public function manualStatus(): BatchStatus
    {
        return $this->batch_status === BatchStatus::Closed
            ? BatchStatus::Closed
            : BatchStatus::Open;
    }

    public function effectiveStatus(): BatchStatus
    {
        if ($this->manualStatus() === BatchStatus::Closed) {
            return BatchStatus::Closed;
        }

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->get();

        if ($items->isNotEmpty() && $items->every(
            fn (BatchItem $item): bool => (float) $item->qty_in_kg <= 0
        )) {
            return BatchStatus::SoldOut;
        }

        return BatchStatus::Open;
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
}

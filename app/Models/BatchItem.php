<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchItem extends Model
{
    use HasFactory;

    protected $table = 'batch_item';

    protected $primaryKey = 'batch_item_id';

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'product_id',
        'qty_in_kg',
        'cost_per_kg',
    ];

    protected function casts(): array
    {
        return [
            'qty_in_kg' => 'decimal:3',
            'cost_per_kg' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id', 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
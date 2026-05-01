<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $primaryKey = 'inventory_id';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'current_stock_kg',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'current_stock_kg' => 'decimal:3',
            'last_updated_at' => 'datetime',
        ];
    }

    public function getCurrentStockAttribute(): mixed
    {
        return $this->current_stock_kg;
    }

    public function setCurrentStockAttribute(mixed $value): void
    {
        $this->attributes['current_stock_kg'] = $value;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $table = 'product';

    protected $primaryKey = 'product_id';

    public $timestamps = false;

    protected $fillable = [
        'product_name',
        'product_category',
        'product_price_per_kilo',
    ];

    protected function casts(): array
    {
        return [
            'product_price_per_kilo' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Product $product): void {
            $product->inventory()->firstOrCreate([], [
                'current_stock_kg' => 0,
                'last_updated_at' => now(),
            ]);
        });
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_id', 'product_id');
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'product_id', 'product_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }
}

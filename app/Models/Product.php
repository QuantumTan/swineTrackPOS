<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 20;

    protected $table = 'product';

    protected $primaryKey = 'product_id';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'product_category',
        'product_name',
        'product_price_per_kilo',
    ];

    protected function casts(): array
    {
        return [
            'product_price_per_kilo' => 'decimal:2',
            'current_stock_kg' => 'decimal:3',
            'last_updated_at' => 'datetime',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
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

    public function getDisplayIdAttribute(): string
    {
        return 'P'.str_pad((string) $this->product_id, 3, '0', STR_PAD_LEFT);
    }

    public function getCurrentStockValueAttribute(): float
    {
        return (float) ($this->current_stock_kg ?? $this->current_stock ?? 0);
    }

    public function getProductCategoryAttribute(): ?string
    {
        return $this->category?->category_name;
    }

    public function setProductCategoryAttribute(?string $value): void
    {
        $categoryName = trim((string) $value);

        if ($categoryName === '') {
            $this->attributes['category_id'] = null;

            return;
        }

        $this->attributes['category_id'] = Category::firstOrCreate([
            'category_name' => $categoryName,
        ])->category_id;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'P'.number_format((float) $this->product_price_per_kilo, 2);
    }

    public function getFormattedStockAttribute(): string
    {
        return number_format($this->current_stock_value, 3).' kg';
    }

    /**
     * @return array{label: string, class: string}
     */
    public function getStockStatusAttribute(): array
    {
        return [
            'label' => $this->current_stock_value <= 0
                ? 'Out of Stock'
                : ($this->current_stock_value < self::LOW_STOCK_THRESHOLD ? 'Low Stock' : 'In Stock'),
            'class' => $this->current_stock_value <= 0
                ? 'danger'
                : ($this->current_stock_value < self::LOW_STOCK_THRESHOLD ? 'warning' : 'success'),
        ];
    }

    public function getFormattedLastUpdatedAttribute(): string
    {
        if (! $this->last_updated_at) {
            return '-';
        }

        return $this->last_updated_at->format('d M Y, h:i A');
    }

    public function getLatestSupplierDisplayAttribute(): string
    {
        return $this->latest_supplier ?: '-';
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category?->category_name ?? '-';
    }
}

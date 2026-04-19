<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_item';

    protected $primaryKey = 'sale_item_id';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'qty_sold_kg',
        'price_per_kg',
    ];

    protected function casts(): array
    {
        return [
            'qty_sold_kg' => 'decimal:3',
            'price_per_kg' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}

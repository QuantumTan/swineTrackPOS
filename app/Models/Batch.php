<?php

namespace App\Models;

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
        ];
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
}
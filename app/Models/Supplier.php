<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $primaryKey = 'supplier_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_name',
        'supplier_phone_number',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'supplier_id', 'supplier_id');
    }
}

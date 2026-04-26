<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $primaryKey = 'supplier_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_name',
        'supplier_contact_first_name',
        'supplier_contact_last_name',
        'supplier_phone_number',
        'supplier_email',
        'supplier_address',
        'supplier_status',
    ];

    protected function casts(): array
    {
        return [
            'batches_max_batch_date' => 'datetime',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'supplier_id', 'supplier_id');
    }

    public function getContactFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->supplier_contact_first_name,
            $this->supplier_contact_last_name,
        ])));
    }

    public function getDisplayIdAttribute(): string
    {
        return 'S'.str_pad((string) $this->supplier_id, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusTypeAttribute(): string
    {
        return $this->supplier_status === 'Active' ? 'success' : 'neutral';
    }

    public function getFormattedLastDeliveryAttribute(): ?string
    {
        if (! $this->batches_max_batch_date) {
            return null;
        }

        return Carbon::parse($this->batches_max_batch_date)->format('d M Y, h:i A');
    }
}

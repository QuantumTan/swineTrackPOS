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
        'contact_person_first_name',
        'contact_person_last_name',
        'contact_number',
        'supplier_status',
        'status',
        'email_address',
        'business_address',
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
            $this->contact_person_first_name,
            $this->contact_person_last_name,
        ])));
    }

    public function getDisplayIdAttribute(): string
    {
        return 'S'.str_pad((string) $this->supplier_id, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusTypeAttribute(): string
    {
        return $this->status === 'Active' ? 'success' : 'neutral';
    }

    public function getSupplierContactFirstNameAttribute(): ?string
    {
        return $this->contact_person_first_name;
    }

    public function setSupplierContactFirstNameAttribute(?string $value): void
    {
        $this->attributes['contact_person_first_name'] = $value;
    }

    public function getSupplierContactLastNameAttribute(): ?string
    {
        return $this->contact_person_last_name;
    }

    public function setSupplierContactLastNameAttribute(?string $value): void
    {
        $this->attributes['contact_person_last_name'] = $value;
    }

    public function getSupplierPhoneNumberAttribute(): ?string
    {
        return $this->contact_number;
    }

    public function setSupplierPhoneNumberAttribute(?string $value): void
    {
        $this->attributes['contact_number'] = $value;
    }

    public function getSupplierEmailAttribute(): ?string
    {
        return $this->email_address;
    }

    public function setSupplierEmailAttribute(?string $value): void
    {
        $this->attributes['email_address'] = $value;
    }

    public function getSupplierAddressAttribute(): ?string
    {
        return $this->business_address;
    }

    public function setSupplierAddressAttribute(?string $value): void
    {
        $this->attributes['business_address'] = $value;
    }

    public function getSupplierStatusAttribute(): ?string
    {
        return $this->status;
    }

    public function setSupplierStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value;
    }

    public function getFormattedLastDeliveryAttribute(): ?string
    {
        if (! $this->batches_max_batch_date) {
            return null;
        }

        return Carbon::parse($this->batches_max_batch_date)->format('d M Y, h:i A');
    }
}

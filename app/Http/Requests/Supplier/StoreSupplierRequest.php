<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:100'],
            'supplier_contact_first_name' => ['nullable', 'string', 'max:50'],
            'supplier_contact_last_name' => ['nullable', 'string', 'max:50'],
            'supplier_phone_number' => ['nullable', 'string', 'max:20'],
            'supplier_email' => ['nullable', 'email', 'max:120'],
            'supplier_address' => ['nullable', 'string', 'max:255'],
            'supplier_payment_terms' => ['nullable', 'string', 'max:80'],
            'supplier_status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'supplier_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'supplier_name',
            'supplier_contact_first_name',
            'supplier_contact_last_name',
            'supplier_phone_number',
            'supplier_email',
            'supplier_address',
            'supplier_payment_terms',
            'supplier_status',
            'supplier_notes',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);

                if ($value === '' && $field !== 'supplier_name') {
                    $value = null;
                }
            }

            $payload[$field] = $value;
        }

        if (! isset($payload['supplier_status']) || $payload['supplier_status'] === null) {
            $payload['supplier_status'] = 'Active';
        }

        $this->merge($payload);
    }
}

<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:100'],
            'contact_person_first_name' => ['nullable', 'string', 'max:50'],
            'contact_person_last_name' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
            'email_address' => ['nullable', 'email', 'max:120'],
            'business_address' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'supplier_name',
            'contact_person_first_name',
            'contact_person_last_name',
            'contact_number',
            'status',
            'email_address',
            'business_address',
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

        if (! isset($payload['status']) || $payload['status'] === null) {
            $payload['status'] = 'Active';
        }

        $this->merge($payload);
    }
}

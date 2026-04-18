<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:100'],
            'supplier_phone_number' => ['nullable', 'string', 'max:15'],
        ];
    }
}

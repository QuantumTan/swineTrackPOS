<?php

namespace App\Http\Requests\StockIn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'batch_date' => ['required', 'date'],
            'source_type' => ['required', Rule::in(['Supplier', 'Own Livestock'])],
            'supplier_id' => ['nullable', 'integer', 'exists:supplier,supplier_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:product,product_id'],
            'items.*.qty_in_kg' => ['required', 'numeric', 'min:0.001'],
            'items.*.cost_per_kg' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('items')) && $this->filled('product_id')) {
            $this->merge([
                'items' => [[
                    'product_id' => $this->input('product_id'),
                    'qty_in_kg' => $this->input('qty_in_kg'),
                    'cost_per_kg' => $this->input('cost_per_kg'),
                ]],
            ]);
        }

        if ($this->input('source_type') !== 'Supplier') {
            $this->merge(['supplier_id' => null]);
        }
    }
}

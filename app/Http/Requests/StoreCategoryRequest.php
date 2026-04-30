<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:50', Rule::unique('category', 'category_name')],
            'category_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_name' => trim((string) $this->input('category_name')),
            'category_description' => $this->filled('category_description')
                ? trim((string) $this->input('category_description'))
                : null,
        ]);
    }
}

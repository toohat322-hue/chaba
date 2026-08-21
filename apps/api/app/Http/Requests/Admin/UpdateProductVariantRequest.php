<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($this->route('variant'))],
            'color' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'size_value' => ['nullable', 'numeric', 'min:0.1'],
            'size_unit' => ['nullable', 'required_with:size_value', 'in:ml,g,oz'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description_ar' => ['nullable', 'string'],
            'description_fr' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'base_price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,active,archived'],
            'featured' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            // A product needs at least one purchasable variant to exist, so
            // creation always seeds one; additional variants are added via
            // ProductVariantController after the product exists.
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'initial_stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1'],
            'size_value' => ['nullable', 'numeric', 'min:0.1'],
            'size_unit' => ['nullable', 'required_with:size_value', 'in:ml,g,oz'],
        ];
    }
}

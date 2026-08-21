<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_fr' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->route('product'))],
            'description_ar' => ['nullable', 'string'],
            'description_fr' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'featured' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'icon' => ['sometimes', 'in:'.implode(',', StoreFooterFeatureRequest::ICONS)],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'title_fr' => ['sometimes', 'string', 'max:255'],
            'title_en' => ['sometimes', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_fr' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

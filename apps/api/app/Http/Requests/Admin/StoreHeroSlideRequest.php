<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeroSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'subtitle_ar' => ['nullable', 'string', 'max:500'],
            'subtitle_fr' => ['nullable', 'string', 'max:500'],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'cta_label_ar' => ['nullable', 'string', 'max:100'],
            'cta_label_fr' => ['nullable', 'string', 'max:100'],
            'cta_label_en' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}

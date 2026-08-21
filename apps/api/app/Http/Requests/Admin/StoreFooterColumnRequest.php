<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooterColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'title_fr' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

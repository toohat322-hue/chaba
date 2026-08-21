<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'title_fr' => ['sometimes', 'string', 'max:255'],
            'title_en' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

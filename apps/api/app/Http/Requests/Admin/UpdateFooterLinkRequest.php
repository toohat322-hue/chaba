<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label_ar' => ['sometimes', 'string', 'max:255'],
            'label_fr' => ['sometimes', 'string', 'max:255'],
            'label_en' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'string', 'max:500', 'regex:/^\//'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

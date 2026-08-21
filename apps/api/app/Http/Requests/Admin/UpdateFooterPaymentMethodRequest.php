<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_fr' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'icon' => ['sometimes', 'in:'.implode(',', StoreFooterPaymentMethodRequest::ICONS)],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

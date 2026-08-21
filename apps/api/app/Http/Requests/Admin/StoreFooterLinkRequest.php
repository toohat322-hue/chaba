<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooterLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label_ar' => ['required', 'string', 'max:255'],
            'label_fr' => ['required', 'string', 'max:255'],
            'label_en' => ['required', 'string', 'max:255'],
            // Internal storefront path (e.g. "/faq", "/category/oud-oil"),
            // not an absolute URL — footer nav links stay within the site.
            'url' => ['required', 'string', 'max:500', 'regex:/^\//'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

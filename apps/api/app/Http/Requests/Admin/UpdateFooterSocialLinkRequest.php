<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['sometimes', 'in:'.implode(',', StoreFooterSocialLinkRequest::PLATFORMS)],
            'url' => ['sometimes', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

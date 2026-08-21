<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooterSocialLinkRequest extends FormRequest
{
    public const PLATFORMS = ['instagram', 'facebook', 'tiktok', 'snapchat', 'twitter', 'youtube', 'whatsapp'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'in:'.implode(',', self::PLATFORMS)],
            'url' => ['required', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

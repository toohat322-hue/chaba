<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooterFeatureRequest extends FormRequest
{
    // Kept in sync by hand with FEATURE_ICONS in
    // apps/web/components/layout/FooterIcons.tsx — same convention this
    // project already uses to keep messages/{ar,fr,en}.json in parity.
    public const ICONS = ['truck', 'shield', 'return', 'badge', 'clock', 'gift', 'star', 'lock'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'icon' => ['required', 'in:'.implode(',', self::ICONS)],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_fr' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_fr' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

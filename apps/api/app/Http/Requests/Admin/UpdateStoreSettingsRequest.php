<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:500'],
            // 10000 bps = 100% — a hard ceiling against a fat-fingered rate
            // silently zeroing out every future order's margin.
            'tax_rate_bps' => ['required', 'integer', 'min:0', 'max:10000'],
            'about_title_ar' => ['nullable', 'string', 'max:255'],
            'about_title_fr' => ['nullable', 'string', 'max:255'],
            'about_title_en' => ['nullable', 'string', 'max:255'],
            'about_description_ar' => ['nullable', 'string', 'max:2000'],
            'about_description_fr' => ['nullable', 'string', 'max:2000'],
            'about_description_en' => ['nullable', 'string', 'max:2000'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'whatsapp_message_ar' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_fr' => ['nullable', 'string', 'max:500'],
            'whatsapp_message_en' => ['nullable', 'string', 'max:500'],
            'whatsapp_active' => ['sometimes', 'boolean'],
            'whatsapp_orders_enabled' => ['sometimes', 'boolean'],
        ];
    }
}

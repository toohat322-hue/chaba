<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooterPaymentMethodRequest extends FormRequest
{
    // Kept in sync by hand with PAYMENT_ICONS in
    // apps/web/components/layout/FooterIcons.tsx. cod/cib/edahabia are the
    // gateways this store actually integrates today; the rest are here so
    // an admin can add a badge ahead of a future real integration.
    public const ICONS = ['cod', 'cib', 'edahabia', 'visa', 'mastercard', 'applepay', 'mada', 'card'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'in:'.implode(',', self::ICONS)],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}

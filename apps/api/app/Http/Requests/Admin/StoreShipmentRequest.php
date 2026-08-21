<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'manual' is the only courier today (ManualCourierProvider) —
            // nullable so it can default to that.
            'courier_partner' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'estimated_delivery_date' => ['nullable', 'date'],
        ];
    }
}

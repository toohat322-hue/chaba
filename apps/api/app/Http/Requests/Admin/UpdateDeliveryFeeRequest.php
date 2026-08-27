<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fee' => ['required', 'integer', 'min:0'],
            'pickup_fee' => ['required', 'integer', 'min:0'],
            'eta_min_days' => ['nullable', 'integer', 'min:0'],
            'eta_max_days' => ['nullable', 'integer', 'gte:eta_min_days'],
        ];
    }
}

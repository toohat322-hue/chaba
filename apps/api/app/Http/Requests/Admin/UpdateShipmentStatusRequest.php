<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:picked_up,in_transit,out_for_delivery,delivered,failed,returned'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'courier_partner' => ['nullable', 'string', 'max:255'],
            'estimated_delivery_date' => ['nullable', 'date'],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

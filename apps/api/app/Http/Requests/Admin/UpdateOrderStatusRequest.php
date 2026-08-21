<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,processing,ready_to_ship,shipped,out_for_delivery,delivered,cancelled,returned,refunded'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:coupons,code'],
            // bxgy intentionally not offered — the enum keeps it only because
            // the column already had it from an earlier migration pass.
            'type' => ['required', 'in:percentage,fixed,free_shipping'],
            'value' => ['required_unless:type,free_shipping', 'nullable', 'integer', 'min:1'],
            'min_order_value' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') === 'percentage' && $this->input('value') > 100) {
                $validator->errors()->add('value', 'A percentage coupon cannot exceed 100.');
            }
        });
    }
}

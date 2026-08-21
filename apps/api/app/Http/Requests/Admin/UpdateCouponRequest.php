<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('coupons', 'code')->ignore($this->route('coupon'))],
            'type' => ['sometimes', 'in:percentage,fixed,free_shipping'],
            'value' => ['nullable', 'integer', 'min:1'],
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
            $type = $this->input('type');
            $value = $this->input('value');

            if ($type === 'percentage' && $value !== null && $value > 100) {
                $validator->errors()->add('value', 'A percentage coupon cannot exceed 100.');
            }
        });
    }
}

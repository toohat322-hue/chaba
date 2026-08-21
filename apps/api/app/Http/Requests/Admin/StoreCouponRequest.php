<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
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
            // Case-insensitive on purpose — CouponService::findValid() matches
            // upper(code), so a plain case-sensitive unique rule would let
            // "SAVE10" and "save10" both validate as "unique" and then
            // collide unpredictably at lookup time (or 500 on the DB's
            // case-insensitive unique index instead of a clean 422).
            'code' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (Coupon::whereRaw('upper(code) = ?', [mb_strtoupper($value)])->exists()) {
                    $fail('This coupon code already exists.');
                }
            }],
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

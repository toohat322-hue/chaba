<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
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
            // Case-insensitive on purpose — see StoreCouponRequest.
            'code' => ['sometimes', 'string', 'max:255', function ($attribute, $value, $fail) {
                $exists = Coupon::whereRaw('upper(code) = ?', [mb_strtoupper($value)])
                    ->where('id', '!=', $this->route('coupon'))
                    ->exists();

                if ($exists) {
                    $fail('This coupon code already exists.');
                }
            }],
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
            // A PATCH can send only `type` (or only `value`) — the cap has to
            // be checked against the coupon's *resulting* state, not just
            // whichever field happened to be in this particular request, or
            // e.g. `{"type":"percentage"}` on a coupon whose existing value
            // is a leftover fixed-amount figure silently produces a coupon
            // worth hundreds of percent off with no validation error.
            $existing = Coupon::find($this->route('coupon'));
            $type = $this->input('type', $existing?->type);
            $value = $this->input('value', $existing?->value);

            if ($type === 'percentage' && $value !== null && $value > 100) {
                $validator->errors()->add('value', 'A percentage coupon cannot exceed 100.');
            }
        });
    }
}

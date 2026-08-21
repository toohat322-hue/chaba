<?php

namespace App\Http\Requests\Auth;

use App\Rules\AlgerianPhone;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->phone)) {
            $normalized = PhoneNormalizer::toE164($this->phone);
            if ($normalized) {
                $this->merge(['phone' => $normalized]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', new AlgerianPhone],
            // password_reset is intentionally excluded: that flow is a single
            // call to POST /auth/password/reset (send code -> verify+change
            // atomically), so a code isn't consumed here and stranded there.
            'purpose' => ['required', 'in:verify,login'],
            'code' => ['required', 'digits:6'],
        ];
    }
}

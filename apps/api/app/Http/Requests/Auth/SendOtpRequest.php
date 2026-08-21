<?php

namespace App\Http\Requests\Auth;

use App\Rules\AlgerianPhone;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'purpose' => ['required', 'in:verify,login,password_reset'],
        ];
    }
}

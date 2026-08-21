<?php

namespace App\Http\Requests;

use App\Rules\AlgerianPhone;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', new AlgerianPhone],
            'wilaya_code' => ['required', 'exists:wilayas,code'],
            'commune_id' => ['required', 'uuid', 'exists:communes,id'],
            'address_line' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ];
    }
}

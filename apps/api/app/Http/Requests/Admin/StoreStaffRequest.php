<?php

namespace App\Http\Requests\Admin;

use App\Rules\AlgerianPhone;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
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
            'phone' => ['required', new AlgerianPhone, 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ];
    }
}

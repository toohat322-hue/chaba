<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize before the `unique` rule runs, not after — otherwise
     * "Test@Example.com" and "test@example.com" both pass validation as
     * distinct values and the second insert fails on the DB constraint
     * instead of surfacing a clean validation error.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => trim(strtolower((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255', 'unique:newsletter_subscribers,email'],
            'locale' => ['nullable', 'in:ar,fr,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already subscribed.',
        ];
    }
}

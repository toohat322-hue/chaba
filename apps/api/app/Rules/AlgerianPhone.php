<?php

namespace App\Rules;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlgerianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNormalizer::isValid($value)) {
            $fail('The :attribute must be a valid Algerian mobile number (e.g. 05XXXXXXXX, 06XXXXXXXX, or 07XXXXXXXX).');
        }
    }
}

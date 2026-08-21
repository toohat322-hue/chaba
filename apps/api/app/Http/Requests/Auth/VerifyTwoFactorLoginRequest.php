<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A recovery code (XXXX-XXXX) or a 6-digit TOTP code — both handled
        // by TwoFactorService::verifyLoginCode, so no format restriction
        // beyond "present" here.
        return [
            'code' => ['required', 'string', 'max:20'],
        ];
    }
}

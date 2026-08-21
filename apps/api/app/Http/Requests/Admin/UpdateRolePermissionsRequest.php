<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_keys' => ['present', 'array'],
            'permission_keys.*' => ['string', 'exists:permissions,key'],
        ];
    }
}

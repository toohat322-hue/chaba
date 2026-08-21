<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['sometimes', 'uuid', 'exists:roles,id'],
            'status' => ['sometimes', 'in:active,blocked'],
        ];
    }
}

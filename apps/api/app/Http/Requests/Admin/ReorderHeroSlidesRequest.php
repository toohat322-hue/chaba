<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderHeroSlidesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slide_ids' => ['required', 'array', 'min:1'],
            'slide_ids.*' => ['required', 'uuid'],
        ];
    }
}

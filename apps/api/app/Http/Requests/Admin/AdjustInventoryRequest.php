<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Positive to add stock, negative to remove — matches
            // inventory_adjustments.delta (signed integer, Phase 1 schema).
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'in:restock,correction,return,damage'],
        ];
    }
}

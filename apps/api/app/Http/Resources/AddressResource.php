<?php

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'wilaya_code' => $this->wilaya_code,
            'commune_id' => $this->commune_id,
            'address_line' => $this->address_line,
            'landmark' => $this->landmark,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
        ];
    }
}

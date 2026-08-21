<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'preferred_language' => $this->preferred_language,
            'phone_verified' => $this->phone_verified_at !== null,
            'email_verified' => $this->email_verified_at !== null,
            'has_password' => $this->password_hash !== null,
            'role' => $this->whenLoaded('role', fn () => $this->role?->name),
            'status' => $this->status,
            'two_factor_enabled' => $this->two_factor_confirmed_at !== null,
        ];
    }
}

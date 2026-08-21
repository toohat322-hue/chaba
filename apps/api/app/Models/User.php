<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids;

    // role_id/status/is_guest are deliberately excluded — every current
    // caller scopes its own request tightly enough that this doesn't matter
    // today (UpdateProfileRequest never validates them, StaffController
    // uses ->only([...])), but the model itself had no guard: the moment
    // any future endpoint did $user->fill($request->all()) with a looser
    // request, a customer could set their own role_id to an admin role or
    // flip status back to 'active' after being blocked. Privileged code
    // paths (registration, staff creation/role changes) use forceFill()
    // instead, which makes the privilege escalation explicit at the call
    // site rather than silently possible through the model.
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password_hash',
        'preferred_language',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'is_guest' => 'boolean',
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            // encrypted: a raw DB dump alone can't be used to mint valid
            // codes or steal recovery codes.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Our password column is `password_hash`, not Laravel's default `password`.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function hasPermission(string $key): bool
    {
        if (! $this->role_id) {
            return false;
        }

        return $this->role->permissions()->where('key', $key)->exists();
    }
}

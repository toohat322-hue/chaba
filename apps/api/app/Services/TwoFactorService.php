<?php

namespace App\Services;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function startSetup(User $user): array
    {
        $secret = Totp::generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();

        return [
            'secret' => $secret,
            'otpauth_url' => Totp::provisioningUri($secret, $user->email ?? $user->phone),
        ];
    }

    /** @return string[]|null Plaintext recovery codes, shown only this once — null if the code was wrong. */
    public function confirmSetup(User $user, string $code): ?array
    {
        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $code)) {
            return null;
        }

        $plainCodes = collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => Str::upper(Str::random(4).'-'.Str::random(4)))
            ->all();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $plainCodes),
        ])->save();

        return $plainCodes;
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null;
    }

    public function verifyLoginCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret && Totp::verify($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }
}

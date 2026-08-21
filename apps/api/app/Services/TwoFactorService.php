<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_LOCKOUT_TTL_SECONDS = 900;

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

    /**
     * Per-user (not per-token) attempt lockout — a valid password alone lets
     * an attacker mint a fresh 5-minute pending token indefinitely, so the
     * counter has to survive across pending tokens rather than reset with
     * each one, the same way OtpService::verify locks out per phone number.
     */
    public function verifyLoginCode(User $user, string $code): bool
    {
        $lockoutKey = $this->key('lockout', $user);

        if (Redis::exists($lockoutKey)) {
            throw new ApiException('two_factor_locked', 'Too many incorrect attempts. Try again later.', 429);
        }

        if (($user->two_factor_secret && Totp::verify($user->two_factor_secret, $code)) || $this->consumeRecoveryCode($user, $code)) {
            Redis::del($this->key('attempts', $user));

            return true;
        }

        $attemptsKey = $this->key('attempts', $user);
        $attempts = Redis::incr($attemptsKey);
        Redis::expire($attemptsKey, self::LOGIN_LOCKOUT_TTL_SECONDS);

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            Redis::setex($lockoutKey, self::LOGIN_LOCKOUT_TTL_SECONDS, '1');
            Redis::del($attemptsKey);
        }

        return false;
    }

    private function key(string $type, User $user): string
    {
        return "2fa:{$type}:{$user->id}";
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

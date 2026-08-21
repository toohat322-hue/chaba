<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * PRD §15: short-lived (15 min) access tokens with rotated refresh tokens.
 * Built on Sanctum (already installed) rather than a new package/table —
 * each pair shares a random pair ID embedded in both tokens' `name`
 * (`access:{pairId}` / `refresh:{pairId}`) so logout/refresh can find and
 * revoke the sibling token without a separate pairing table.
 */
class TokenService
{
    private const ACCESS_TTL_MINUTES = 15;

    private const REFRESH_TTL_DAYS = 30;

    private const TWO_FACTOR_PENDING_TTL_MINUTES = 5;

    /** @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int} */
    public function issuePair(User $user): array
    {
        $pairId = (string) Str::uuid();

        $access = $user->createToken("access:{$pairId}", ['access-api'], now()->addMinutes(self::ACCESS_TTL_MINUTES));
        $refresh = $user->createToken("refresh:{$pairId}", ['refresh-token'], now()->addDays(self::REFRESH_TTL_DAYS));

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TTL_MINUTES * 60,
        ];
    }

    /**
     * Issued instead of a real token pair when LoginController finds 2FA
     * enabled — carries only the 'two-factor-pending' ability, so it can't
     * be used against any real endpoint until TwoFactorLoginController
     * exchanges it (and revokes it) for a real pair.
     */
    public function issuePendingTwoFactorToken(User $user): array
    {
        $token = $user->createToken('2fa-pending', ['two-factor-pending'], now()->addMinutes(self::TWO_FACTOR_PENDING_TTL_MINUTES));

        return [
            'two_factor_token' => $token->plainTextToken,
            'expires_in' => self::TWO_FACTOR_PENDING_TTL_MINUTES * 60,
        ];
    }

    public function revokePair(User $user, string $tokenName): void
    {
        if (! str_contains($tokenName, ':')) {
            return;
        }

        $pairId = Str::after($tokenName, ':');

        $user->tokens()
            ->whereIn('name', ["access:{$pairId}", "refresh:{$pairId}"])
            ->delete();
    }
}

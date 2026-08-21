<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * OAuth `state` for social login, without a PHP session — this API has none
 * (see bootstrap/app.php: "pure JSON API... no Laravel-rendered login
 * page"). The state itself is a Crypt::encryptString() blob (APP_KEY-signed,
 * tamper-proof, self-describing: nonce/issued_at/return_to/link_user_id),
 * so no server-side storage is needed to verify its *integrity* — Socialite
 * is used in ->stateless() mode with this value manually injected.
 *
 * Integrity alone isn't enough though: an attacker can start a real OAuth
 * flow for their own account, capture the resulting valid callback URL, and
 * send it to a victim — the encrypted state would still decrypt and verify
 * fine, logging the victim's browser into the attacker's identity (a
 * classic OAuth login-CSRF replay). The nonce cookie closes that gap: it's
 * set on the same response that starts the redirect and must be echoed back
 * unchanged by the *same browser* on the callback, binding the flow to
 * whoever actually initiated it.
 */
class SocialStateService
{
    private const TTL_SECONDS = 600;

    public const COOKIE_NAME = 'chaba_oauth_nonce';

    /**
     * @return array{state: string, nonce: string}
     */
    public function generate(string $returnTo, string $locale, ?string $linkUserId = null): array
    {
        $nonce = Str::random(40);

        $payload = [
            'nonce' => $nonce,
            'issued_at' => now()->timestamp,
            'return_to' => $returnTo,
            'locale' => $locale,
            'link_user_id' => $linkUserId,
        ];

        return [
            'state' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'nonce' => $nonce,
        ];
    }

    /**
     * @return array{nonce: string, issued_at: int, return_to: string, locale: string, link_user_id: ?string}|null
     *         null if the state is malformed, tampered with, or expired.
     */
    public function decode(string $state, ?string $cookieNonce): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException) {
            return null;
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['nonce'], $payload['issued_at'], $payload['return_to'], $payload['locale'])) {
            return null;
        }

        if (now()->timestamp - (int) $payload['issued_at'] > self::TTL_SECONDS) {
            return null;
        }

        if (! $cookieNonce || ! hash_equals($payload['nonce'], $cookieNonce)) {
            return null;
        }

        return $payload;
    }

    public function cookieTtlMinutes(): int
    {
        return (int) ceil(self::TTL_SECONDS / 60);
    }
}

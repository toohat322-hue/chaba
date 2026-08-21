<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * The one-time bridge between "the OAuth callback resolved a user, on the
 * backend" and "the frontend has real tokens, in a JSON response body" —
 * real tokens never appear in a URL (query string, fragment, browser
 * history, referrer headers, server access logs). Mirrors OtpService's
 * exact Redis-ephemeral-value pattern: short TTL, single-use (deleted on
 * first successful read), no DB table since this is intentionally
 * throwaway state.
 *
 * Stores the *exact response payload* the callback already built (either a
 * full {user, access_token, refresh_token, ...} pair, or a
 * {requires_two_factor: true, ...pending token...} shape) — same envelope
 * shape LoginController itself returns for the equivalent cases — so the
 * exchange endpoint only ever replays what was decided once, rather than
 * re-deriving auth decisions a second time.
 */
class SocialExchangeService
{
    private const TTL_SECONDS = 60;

    public function store(array $payload): string
    {
        $code = Str::random(64);

        Redis::setex($this->key($code), self::TTL_SECONDS, json_encode($payload, JSON_THROW_ON_ERROR));

        return $code;
    }

    public function redeem(string $code): ?array
    {
        $key = $this->key($code);
        $raw = Redis::get($key);

        if (! $raw) {
            return null;
        }

        Redis::del($key);

        return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    }

    private function key(string $code): string
    {
        return "social_exchange:{$code}";
    }
}

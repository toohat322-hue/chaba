<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Lets an already-authenticated customer start "connect my Google account"
 * from their account page (a normal Bearer-authenticated fetch) and then
 * hand off to a full-page browser navigation to /auth/{provider}/redirect
 * (which can't carry an Authorization header) without needing cookies/CORS
 * credentials for that handoff. The ticket is single-use and short-lived —
 * same Redis-ephemeral pattern as OtpService/SocialExchangeService — and is
 * only ever a pointer to *whose* account to link; the real security for the
 * link itself still comes from SocialStateService's signed state + nonce
 * cookie once the redirect actually happens.
 */
class SocialLinkTicketService
{
    private const TTL_SECONDS = 120;

    public function store(User $user): string
    {
        $ticket = Str::random(64);

        Redis::setex($this->key($ticket), self::TTL_SECONDS, $user->id);

        return $ticket;
    }

    public function redeem(string $ticket): ?string
    {
        $key = $this->key($ticket);
        $userId = Redis::get($key);

        if (! $userId) {
            return null;
        }

        Redis::del($key);

        return $userId;
    }

    private function key(string $ticket): string
    {
        return "social_link:{$ticket}";
    }
}

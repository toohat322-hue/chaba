<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use App\Services\SocialExchangeService;
use App\Services\SocialStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialCallbackController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'apple'];

    public function __construct(
        private readonly SocialStateService $state,
        private readonly SocialAuthService $auth,
        private readonly SocialExchangeService $exchange,
    ) {}

    /**
     * Accepts GET (Google/Facebook's normal redirect) and POST (Apple's
     * form_post response mode — required because Apple posts the user's
     * name as a JSON body field, only on the very first authorization).
     * Always ends in a browser redirect back to the frontend — this route
     * is reached by full-page navigation, not fetch, so it can never return
     * raw JSON to a human; every failure path below redirects with an
     * `?error=` code the frontend callback page turns into a friendly
     * message instead.
     */
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $cookieNonce = $request->cookie(SocialStateService::COOKIE_NAME);
        $clearNonceCookie = cookie()->forget(SocialStateService::COOKIE_NAME, '/api/v1/auth');

        $decoded = $this->state->decode((string) $request->input('state', ''), $cookieNonce);

        if (! $decoded) {
            return $this->errorRedirect('ar', 'invalid_state')->withCookie($clearNonceCookie);
        }

        $locale = $decoded['locale'];

        if ($request->input('error')) {
            // The provider itself reports a problem (most commonly the user
            // cancelled/denied consent) — nothing to log as unexpected here.
            return $this->errorRedirect($locale, 'provider_denied', $decoded['return_to'])->withCookie($clearNonceCookie);
        }

        if (! config("services.{$provider}.enabled")) {
            return $this->errorRedirect($locale, 'provider_not_configured', $decoded['return_to'])->withCookie($clearNonceCookie);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();
        } catch (InvalidStateException|\Exception $e) {
            Log::warning("Social login callback failed for {$provider}", ['message' => $e->getMessage()]);

            return $this->errorRedirect($locale, 'provider_error', $decoded['return_to'])->withCookie($clearNonceCookie);
        }

        $providerId = $socialiteUser->getId();
        $email = $socialiteUser->getEmail();
        $name = $socialiteUser->getName();

        if (! $providerId) {
            return $this->errorRedirect($locale, 'provider_error', $decoded['return_to'])->withCookie($clearNonceCookie);
        }

        try {
            // Google/Facebook/Apple only ever hand back an email that's
            // already confirmed/verified by their own platform (Apple's
            // Private Relay addresses are themselves an Apple-verified proxy)
            // — there's no separate "unverified email" state any of the
            // three providers can report through their standard OAuth/OIDC
            // response, so this is always true whenever email is present.
            $payload = $this->auth->resolve(
                provider: $provider,
                providerId: (string) $providerId,
                email: $email,
                emailVerifiedByProvider: true,
                name: $name,
                linkUserId: $decoded['link_user_id'] ?? null,
            );
        } catch (ApiException $e) {
            return $this->errorRedirect($locale, $e->errorCode, $decoded['return_to'])->withCookie($clearNonceCookie);
        }

        $code = $this->exchange->store($payload);
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/{$locale}/auth/callback?".http_build_query([
            'code' => $code,
            'return_to' => $decoded['return_to'],
        ]))->withCookie($clearNonceCookie);
    }

    private function errorRedirect(string $locale, string $errorCode, string $returnTo = '/'): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/{$locale}/auth/callback?".http_build_query([
            'error' => $errorCode,
            'return_to' => $returnTo,
        ]));
    }
}

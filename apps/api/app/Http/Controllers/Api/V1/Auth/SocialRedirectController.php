<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialLinkTicketService;
use App\Services\SocialStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Cookie;

class SocialRedirectController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'apple'];

    private const LOCALES = ['ar', 'fr', 'en'];

    public function __construct(
        private readonly SocialStateService $state,
        private readonly SocialLinkTicketService $linkTickets,
    ) {}

    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $locale = in_array($request->query('locale'), self::LOCALES, true) ? $request->query('locale') : 'ar';

        if (! config("services.{$provider}.enabled")) {
            return redirect("{$frontendUrl}/{$locale}/auth/callback?error=provider_not_configured");
        }

        $returnTo = $this->safeReturnTo($request->query('return_to'));

        $linkUserId = null;
        $linkTicket = $request->query('link_ticket');
        if (is_string($linkTicket) && $linkTicket !== '') {
            $linkUserId = $this->linkTickets->redeem($linkTicket);
            // An invalid/expired/already-used ticket just falls back to a
            // normal anonymous login rather than erroring — the user still
            // ends up authenticated as themselves either way, they just
            // won't get the new provider linked and can retry from Account
            // Settings.
        }

        ['state' => $encryptedState, 'nonce' => $nonce] = $this->state->generate($returnTo, $locale, $linkUserId);

        $redirect = Socialite::driver($provider)
            ->stateless()
            ->with(['state' => $encryptedState])
            ->redirect();

        // Apple's callback is a cross-site POST (form_post response mode) —
        // SameSite=Lax cookies aren't sent on cross-site POST navigations,
        // only cross-site GET, so Apple needs None. Google/Facebook use a
        // plain GET redirect, where Lax already works and is the safer
        // default (sent only on top-level navigation, never on embeds).
        $sameSite = $provider === 'apple' ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX;

        $redirect->withCookie(cookie(
            name: SocialStateService::COOKIE_NAME,
            value: $nonce,
            minutes: $this->state->cookieTtlMinutes(),
            path: '/api/v1/auth',
            secure: app()->environment('production'),
            httpOnly: true,
            sameSite: $sameSite,
        ));

        return $redirect;
    }

    /**
     * Same relative-path-only rule the frontend mirrors before it navigates
     * anywhere with this value — never trust the client's own validation,
     * since return_to arrives here as ordinary user-controlled query input.
     * Rejects anything with a scheme, a protocol-relative `//` prefix, or
     * that doesn't start with a single `/` (the only shape a same-origin
     * storefront path can have).
     */
    private function safeReturnTo(?string $value): string
    {
        if (! is_string($value) || $value === '') {
            return '/';
        }

        if (! str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '://')) {
            return '/';
        }

        return $value;
    }
}

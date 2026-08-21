<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Services\SocialExchangeService;
use App\Services\SocialStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * HTTP-level wiring: redirect -> callback -> exchange, exactly as a real
 * browser round-trip would hit these routes. Socialite itself is mocked
 * (no real provider ever contacted) — this is what's fully automatable
 * without real Google/Facebook/Apple credentials; the actual consent
 * screens remain a manual, post-launch verification step.
 */
class SocialLoginHttpTest extends TestCase
{
    use RefreshDatabase;

    private function enableProvider(string $provider): void
    {
        config([
            "services.{$provider}.enabled" => true,
            "services.{$provider}.client_id" => 'test-client-id',
            "services.{$provider}.client_secret" => 'test-client-secret',
            "services.{$provider}.redirect" => 'http://localhost:8000/api/v1/auth/'.$provider.'/callback',
        ]);
    }

    private function mockSocialiteUser(string $provider, string $id, ?string $email, ?string $name): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw(['sub' => $id])->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ]);

        $mockProvider = Mockery::mock(Provider::class);
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($mockProvider);
    }

    public function test_redirect_fails_cleanly_when_the_provider_is_not_configured(): void
    {
        config(['services.google.enabled' => false]);

        $response = $this->get('/api/v1/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('error=provider_not_configured', $response->headers->get('Location'));
    }

    public function test_redirect_sets_the_nonce_cookie_and_points_at_the_provider(): void
    {
        $this->enableProvider('google');

        $response = $this->get('/api/v1/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        $this->assertNotNull($response->headers->getCookies()[0] ?? null);
        $found = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === SocialStateService::COOKIE_NAME);
        $this->assertNotNull($found);
        $this->assertTrue($found->isHttpOnly());
    }

    public function test_return_to_pointing_off_site_is_sanitized_to_the_homepage(): void
    {
        $this->enableProvider('google');

        $response = $this->get('/api/v1/auth/google/redirect?'.http_build_query(['return_to' => 'https://evil.com/phish']));
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $decoded = app(SocialStateService::class)->decode(
            $query['state'],
            collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === SocialStateService::COOKIE_NAME)?->getValue(),
        );

        $this->assertSame('/', $decoded['return_to']);
    }

    public function test_a_full_new_user_google_login_reaches_the_frontend_callback_with_an_exchange_code(): void
    {
        $this->enableProvider('google');
        $this->mockSocialiteUser('google', 'g-123', 'amina@example.com', 'Amina Test');

        ['state' => $state, 'nonce' => $nonce] = app(SocialStateService::class)->generate('/checkout', 'ar');

        $response = $this
            ->withUnencryptedCookie(SocialStateService::COOKIE_NAME, $nonce)
            ->get('/api/v1/auth/google/callback?state='.urlencode($state));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/ar/auth/callback', $location);
        $this->assertStringContainsString('return_to=%2Fcheckout', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);

        $exchangeResponse = $this->postJson('/api/v1/auth/social/exchange', ['code' => $query['code']]);
        $exchangeResponse->assertStatus(200)->assertJsonPath('data.user.email', 'amina@example.com');

        $this->assertTrue(SocialAccount::where('provider', 'google')->where('provider_id', 'g-123')->exists());
    }

    public function test_a_tampered_state_is_rejected_without_ever_calling_the_provider(): void
    {
        $this->enableProvider('google');

        $response = $this
            ->withUnencryptedCookie(SocialStateService::COOKIE_NAME, 'whatever-nonce')
            ->get('/api/v1/auth/google/callback?state=not-a-real-encrypted-value');

        $response->assertRedirect();
        $this->assertStringContainsString('error=invalid_state', $response->headers->get('Location'));
    }

    public function test_a_missing_nonce_cookie_is_rejected_even_with_a_valid_state(): void
    {
        $this->enableProvider('google');
        ['state' => $state] = app(SocialStateService::class)->generate('/', 'ar');

        // No withCookie() call at all — simulates a replayed callback URL
        // sent to a different browser than the one that started the flow.
        $response = $this->get('/api/v1/auth/google/callback?state='.urlencode($state));

        $response->assertRedirect();
        $this->assertStringContainsString('error=invalid_state', $response->headers->get('Location'));
    }

    public function test_a_mismatched_nonce_cookie_is_rejected(): void
    {
        $this->enableProvider('google');
        ['state' => $state] = app(SocialStateService::class)->generate('/', 'ar');

        $response = $this
            ->withUnencryptedCookie(SocialStateService::COOKIE_NAME, 'a-completely-different-nonce')
            ->get('/api/v1/auth/google/callback?state='.urlencode($state));

        $response->assertRedirect();
        $this->assertStringContainsString('error=invalid_state', $response->headers->get('Location'));
    }

    public function test_a_provider_denied_error_is_passed_through_cleanly(): void
    {
        $this->enableProvider('google');
        ['state' => $state, 'nonce' => $nonce] = app(SocialStateService::class)->generate('/', 'ar');

        $response = $this
            ->withUnencryptedCookie(SocialStateService::COOKIE_NAME, $nonce)
            ->get('/api/v1/auth/google/callback?'.http_build_query(['state' => $state, 'error' => 'access_denied']));

        $response->assertRedirect();
        $this->assertStringContainsString('error=provider_denied', $response->headers->get('Location'));
    }

    public function test_exchange_code_is_single_use(): void
    {
        $service = app(SocialExchangeService::class);
        $code = $service->store(['user' => ['id' => 'x'], 'access_token' => 'a', 'refresh_token' => 'r', 'token_type' => 'Bearer', 'expires_in' => 900]);

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])->assertStatus(200);
        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'invalid_or_expired_code');
    }

    public function test_apple_callback_accepts_a_post_request(): void
    {
        $this->enableProvider('apple');
        $this->mockSocialiteUser('apple', 'apple-1', 'amina@privaterelay.appleid.com', 'Amina Test');
        ['state' => $state, 'nonce' => $nonce] = app(SocialStateService::class)->generate('/', 'ar');

        $response = $this
            ->withUnencryptedCookie(SocialStateService::COOKIE_NAME, $nonce)
            ->post('/api/v1/auth/apple/callback', ['state' => $state]);

        $response->assertRedirect();
        $this->assertStringContainsString('/ar/auth/callback', $response->headers->get('Location'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

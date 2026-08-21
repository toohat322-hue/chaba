<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialLinkTicketService;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SocialAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $pair = app(TokenService::class)->issuePair($user);

        return ['Authorization' => "Bearer {$pair['access_token']}"];
    }

    public function test_lists_connected_and_unconnected_providers(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-1', 'provider_email' => 'amina@example.com']);

        $response = $this->withHeaders($this->authHeaders($user))->getJson('/api/v1/users/me/social-accounts');

        $response->assertStatus(200);
        $accounts = collect($response->json('data.accounts'))->keyBy('provider');
        $this->assertTrue($accounts['google']['connected']);
        $this->assertFalse($accounts['facebook']['connected']);
        $this->assertFalse($accounts['apple']['connected']);
    }

    public function test_unlink_succeeds_when_a_password_still_provides_another_way_in(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-1', 'provider_email' => 'amina@example.com']);

        $response = $this->withHeaders($this->authHeaders($user))->deleteJson('/api/v1/users/me/social-accounts/google');

        $response->assertStatus(200);
        $this->assertSame(0, SocialAccount::where('user_id', $user->id)->count());
    }

    public function test_unlink_is_refused_when_it_is_the_only_login_method(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => null, 'status' => 'active',
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-1', 'provider_email' => 'amina@example.com']);

        $response = $this->withHeaders($this->authHeaders($user))->deleteJson('/api/v1/users/me/social-accounts/google');

        $response->assertStatus(422)->assertJsonPath('error.code', 'last_login_method');
        $this->assertSame(1, SocialAccount::where('user_id', $user->id)->count());
    }

    public function test_setting_a_password_then_unlocks_unlinking_the_only_provider(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => null, 'status' => 'active',
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-1', 'provider_email' => 'amina@example.com']);
        $headers = $this->authHeaders($user);

        $this->withHeaders($headers)->postJson('/api/v1/users/me/password/set', [
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password_hash));

        $this->withHeaders($headers)->deleteJson('/api/v1/users/me/social-accounts/google')->assertStatus(200);
    }

    public function test_set_password_is_refused_when_a_password_already_exists(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('existing123'), 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson('/api/v1/users/me/password/set', [
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'password_already_set');
    }

    public function test_link_token_endpoint_returns_a_redirect_url_carrying_the_ticket(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson('/api/v1/users/me/social-accounts/google/link-token');

        $response->assertStatus(200);
        $url = $response->json('data.redirect_url');
        $this->assertStringContainsString('/api/v1/auth/google/redirect', $url);
        $this->assertStringContainsString('link_ticket=', $url);
    }

    public function test_a_link_ticket_attaches_the_new_provider_to_the_current_user_via_the_full_redirect_flow(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'x', 'services.google.client_secret' => 'y', 'services.google.redirect' => 'z',
        ]);

        $ticket = app(SocialLinkTicketService::class)->store($user);

        $response = $this->get('/api/v1/auth/google/redirect?link_ticket='.$ticket);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $nonce = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === \App\Services\SocialStateService::COOKIE_NAME)?->getValue();

        $decoded = app(\App\Services\SocialStateService::class)->decode($query['state'], $nonce);
        $this->assertSame($user->id, $decoded['link_user_id']);
    }
}

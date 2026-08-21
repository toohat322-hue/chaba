<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TokenService;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    /** @return array{0: User, 1: array<string,string>} */
    private function login(User $user): array
    {
        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    public function test_full_setup_flow_enables_two_factor_and_returns_recovery_codes(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->login($user);

        $setup = $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa')->assertStatus(200);
        $secret = $setup->json('data.secret');
        $this->assertNotEmpty($setup->json('data.otpauth_url'));

        $code = $this->generateCode($secret);

        $confirm = $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa/confirm', ['code' => $code]);

        $confirm->assertStatus(200)->assertJsonCount(8, 'data.recovery_codes');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirm_rejects_a_wrong_code(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->login($user);

        $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa');

        $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa/confirm', ['code' => '000000'])
            ->assertStatus(400)->assertJsonPath('error.code', 'two_factor_invalid');
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_login_for_a_two_factor_enabled_user_requires_a_second_step(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());

        $response = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);

        $response->assertStatus(200)
            ->assertJsonPath('data.requires_two_factor', true)
            ->assertJsonStructure(['data' => ['two_factor_token']]);
        $this->assertArrayNotHasKey('access_token', $response->json('data'));
    }

    public function test_the_pending_token_completes_login_with_a_valid_totp_code(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());
        $secret = $user->fresh()->two_factor_secret;

        $loginResponse = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);
        $pendingToken = $loginResponse->json('data.two_factor_token');

        $response = $this->withHeaders(['Authorization' => "Bearer {$pendingToken}"])
            ->postJson('/api/v1/auth/2fa/verify', ['code' => $this->generateCode($secret)]);

        // 'role' must be present (even if null) — UserResource's 'role' key
        // is whenLoaded('role'), so it's silently omitted rather than null
        // if the controller forgets to eager-load the relation. The
        // frontend treats a missing role as "not an admin" and rejects the
        // login, so this has to actually be checked, not just access_token
        // /refresh_token being present.
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'user' => ['role']]])
            ->assertJsonPath('data.user.role', null);
    }

    public function test_the_pending_token_cannot_be_reused_after_a_successful_verify(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());
        $secret = $user->fresh()->two_factor_secret;

        $loginResponse = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);
        $pendingToken = $loginResponse->json('data.two_factor_token');
        $headers = ['Authorization' => "Bearer {$pendingToken}"];

        $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/verify', ['code' => $this->generateCode($secret)])
            ->assertStatus(200);

        $this->withHeaders($headers)->postJson('/api/v1/auth/2fa/verify', ['code' => $this->generateCode($secret)])
            ->assertStatus(401);
    }

    public function test_the_pending_token_cannot_be_used_against_a_normal_endpoint(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());

        $loginResponse = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);
        $pendingToken = $loginResponse->json('data.two_factor_token');

        $this->withHeaders(['Authorization' => "Bearer {$pendingToken}"])
            ->getJson('/api/v1/users/me')
            ->assertStatus(403);
    }

    public function test_a_recovery_code_completes_login_and_is_consumed(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());
        [, $headers] = $this->login($user);
        $recoveryCode = $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa/confirm', [
            'code' => $this->generateCode($user->fresh()->two_factor_secret),
        ])->json('data.recovery_codes.0');

        $loginResponse = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);
        $pendingToken = $loginResponse->json('data.two_factor_token');

        $this->withHeaders(['Authorization' => "Bearer {$pendingToken}"])
            ->postJson('/api/v1/auth/2fa/verify', ['code' => $recoveryCode])
            ->assertStatus(200);

        // Same recovery code cannot be reused for a second login.
        $secondLogin = $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'password123']);
        $secondPendingToken = $secondLogin->json('data.two_factor_token');

        $this->withHeaders(['Authorization' => "Bearer {$secondPendingToken}"])
            ->postJson('/api/v1/auth/2fa/verify', ['code' => $recoveryCode])
            ->assertStatus(400);
    }

    public function test_disabling_two_factor_requires_the_correct_password(): void
    {
        $user = $this->enableTwoFactor($this->makeUser());
        [, $headers] = $this->login($user);

        $this->withHeaders($headers)->deleteJson('/api/v1/users/me/2fa', ['password' => 'wrong'])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_current_password');

        $this->withHeaders($headers)->deleteJson('/api/v1/users/me/2fa', ['password' => 'password123'])
            ->assertStatus(200);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    private function enableTwoFactor(User $user): User
    {
        [, $headers] = $this->login($user);
        $secret = $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa')->json('data.secret');
        $this->withHeaders($headers)->postJson('/api/v1/users/me/2fa/confirm', ['code' => $this->generateCode($secret)]);

        return $user->fresh();
    }

    private function generateCode(string $secret): string
    {
        $ref = new \ReflectionMethod(Totp::class, 'generateCode');
        $ref->setAccessible(true);

        return $ref->invoke(null, $secret, (int) floor(time() / 30));
    }
}

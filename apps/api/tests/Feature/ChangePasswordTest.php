<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: array<string, string>} */
    private function makeAuthedCustomer(): array
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'password_hash' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    public function test_changing_the_password_with_the_wrong_current_password_is_rejected(): void
    {
        [, $headers] = $this->makeAuthedCustomer();

        $response = $this->withHeaders($headers)->patchJson('/api/v1/users/me/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'invalid_current_password');
    }

    public function test_changing_the_password_succeeds_and_revokes_every_session(): void
    {
        [$user, $headers] = $this->makeAuthedCustomer();

        $response = $this->withHeaders($headers)->patchJson('/api/v1/users/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password_hash));

        // The old access token no longer works — every session was revoked.
        $this->withHeaders($headers)->getJson('/api/v1/users/me')->assertStatus(401);

        // Logging back in with the new password works.
        $this->postJson('/api/v1/auth/login', ['login' => '0555222333', 'password' => 'newpassword123'])
            ->assertStatus(200);
    }

    public function test_changing_the_password_requires_confirmation_to_match(): void
    {
        [, $headers] = $this->makeAuthedCustomer();

        $this->withHeaders($headers)->patchJson('/api/v1/users/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'doesnotmatch',
        ])->assertStatus(400);
    }
}

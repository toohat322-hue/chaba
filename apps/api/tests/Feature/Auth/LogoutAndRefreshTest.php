<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutAndRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithTokens(): array
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555333444',
            'password_hash' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $pair = app(TokenService::class)->issuePair($user);

        return [$user, $pair];
    }

    public function test_refresh_rotates_tokens_and_the_old_refresh_token_becomes_unusable(): void
    {
        [, $pair] = $this->makeUserWithTokens();

        $first = $this->withHeader('Authorization', "Bearer {$pair['refresh_token']}")
            ->postJson('/api/v1/auth/refresh');

        $first->assertStatus(200)->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);

        $replay = $this->withHeader('Authorization', "Bearer {$pair['refresh_token']}")
            ->postJson('/api/v1/auth/refresh');

        $replay->assertStatus(401);
    }

    public function test_an_access_token_cannot_be_used_to_refresh(): void
    {
        [, $pair] = $this->makeUserWithTokens();

        $response = $this->withHeader('Authorization', "Bearer {$pair['access_token']}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(403);
    }

    public function test_a_refresh_token_cannot_be_used_on_normal_routes(): void
    {
        [, $pair] = $this->makeUserWithTokens();

        $response = $this->withHeader('Authorization', "Bearer {$pair['refresh_token']}")
            ->getJson('/api/v1/users/me');

        $response->assertStatus(403);
    }

    public function test_logout_revokes_both_the_access_and_refresh_token(): void
    {
        [, $pair] = $this->makeUserWithTokens();

        $this->withHeader('Authorization', "Bearer {$pair['access_token']}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$pair['access_token']}")
            ->getJson('/api/v1/users/me')
            ->assertStatus(401);

        $this->withHeader('Authorization', "Bearer {$pair['refresh_token']}")
            ->postJson('/api/v1/auth/refresh')
            ->assertStatus(401);
    }

    public function test_unauthenticated_requests_get_a_clean_json_401(): void
    {
        $this->getJson('/api/v1/users/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    }
}

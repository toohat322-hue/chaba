<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMeTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $pair = app(TokenService::class)->issuePair($user);

        return ['Authorization' => "Bearer {$pair['access_token']}"];
    }

    public function test_get_me_returns_the_authenticated_user(): void
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555666777',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->withHeaders($this->authHeader($user))
            ->getJson('/api/v1/users/me')
            ->assertStatus(200)
            ->assertJsonPath('data.phone', '+213555666777');
    }

    public function test_patch_me_updates_allowed_fields_only(): void
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555666778',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->withHeaders($this->authHeader($user))
            ->patchJson('/api/v1/users/me', ['full_name' => 'Amina Updated', 'preferred_language' => 'fr'])
            ->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Amina Updated')
            ->assertJsonPath('data.preferred_language', 'fr');

        $this->assertSame('Amina Updated', $user->fresh()->full_name);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/users/me')->assertStatus(401);
    }
}

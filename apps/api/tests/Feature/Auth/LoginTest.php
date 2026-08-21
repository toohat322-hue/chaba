<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'),
            'status' => 'active',
        ], $overrides));
    }

    public function test_login_succeeds_with_phone_and_correct_password(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555222333',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_login_succeeds_with_email_and_correct_password(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'amina@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555222333',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_login_fails_for_an_unknown_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555999888',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_login_is_blocked_for_a_blocked_account(): void
    {
        $this->makeUser(['status' => 'blocked']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555222333',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)->assertJsonPath('error.code', 'account_blocked');
    }

    public function test_login_response_includes_the_role_name_for_a_staff_user(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'description' => 'test']);
        $this->makeUser(['role_id' => $role->id]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555222333',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.user.role', 'Super Admin');
    }

    public function test_login_response_has_a_null_role_for_a_plain_customer(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '0555222333',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.user.role', null);
    }
}

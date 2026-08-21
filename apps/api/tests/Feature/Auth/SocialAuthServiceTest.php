<?php

namespace Tests\Feature\Auth;

use App\Exceptions\ApiException;
use App\Models\Role;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises SocialAuthService::resolve() directly — no HTTP, no Socialite
 * mocking needed here since the service is deliberately Socialite-agnostic
 * (takes plain scalars). The HTTP-level callback/exchange wiring is covered
 * separately in SocialLoginHttpTest.
 */
class SocialAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SocialAuthService
    {
        return app(SocialAuthService::class);
    }

    public function test_a_brand_new_google_user_is_created_with_no_phone_and_no_password(): void
    {
        $payload = $this->service()->resolve('google', 'g-123', 'amina@example.com', true, 'Amina Test');

        $this->assertArrayHasKey('access_token', $payload);
        $user = User::where('email', 'amina@example.com')->firstOrFail();

        $this->assertNull($user->phone);
        $this->assertNull($user->password_hash);
        $this->assertNull($user->role_id);
        $this->assertSame('active', $user->status);
        $this->assertTrue(SocialAccount::where('provider', 'google')->where('provider_id', 'g-123')->where('user_id', $user->id)->exists());
    }

    public function test_a_new_user_can_be_created_with_no_email_at_all(): void
    {
        $payload = $this->service()->resolve('apple', 'apple-999', null, true, null);

        $this->assertArrayHasKey('access_token', $payload);
        $account = SocialAccount::where('provider', 'apple')->where('provider_id', 'apple-999')->firstOrFail();
        $this->assertNull($account->user->email);
        $this->assertSame('CHABA Customer', $account->user->full_name);
    }

    public function test_a_repeat_login_is_recognized_via_the_social_account_match_without_creating_a_duplicate(): void
    {
        $first = $this->service()->resolve('google', 'g-123', 'amina@example.com', true, 'Amina Test');
        $second = $this->service()->resolve('google', 'g-123', 'amina@example.com', true, 'Amina Test');

        $this->assertSame(1, User::where('email', 'amina@example.com')->count());
        $this->assertSame($first['user']['id'], $second['user']['id']);
    }

    public function test_a_repeat_apple_login_without_a_resent_email_still_matches_via_provider_id(): void
    {
        $this->service()->resolve('apple', 'apple-777', 'amina@privaterelay.appleid.com', true, 'Amina Test');

        // Apple only resends the email on the very first authorization —
        // subsequent logins may omit it entirely.
        $second = $this->service()->resolve('apple', 'apple-777', null, true, null);

        $this->assertSame(1, User::where('email', 'amina@privaterelay.appleid.com')->count());
        $this->assertArrayHasKey('access_token', $second);
    }

    public function test_a_verified_email_match_links_to_the_existing_customer_instead_of_duplicating(): void
    {
        $existing = User::create([
            'full_name' => 'Amina Existing', 'phone' => '+213555111222', 'email' => 'amina@example.com',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);

        $payload = $this->service()->resolve('facebook', 'fb-1', 'amina@example.com', true, 'Amina From Facebook');

        $this->assertSame(1, User::where('email', 'amina@example.com')->count());
        $this->assertSame($existing->id, $payload['user']['id']);
        $this->assertTrue(SocialAccount::where('provider', 'facebook')->where('provider_id', 'fb-1')->where('user_id', $existing->id)->exists());
    }

    public function test_repeat_login_never_overwrites_a_name_the_customer_already_edited(): void
    {
        $this->service()->resolve('google', 'g-1', 'amina@example.com', true, 'Amina Original');

        $user = User::where('email', 'amina@example.com')->firstOrFail();
        $user->update(['full_name' => 'Amina Edited By Customer']);

        $this->service()->resolve('google', 'g-1', 'amina@example.com', true, 'Amina From Google Profile');

        $this->assertSame('Amina Edited By Customer', $user->fresh()->full_name);
    }

    public function test_a_verified_email_matching_a_staff_account_is_hard_refused(): void
    {
        $role = Role::create(['name' => 'Order Manager']);
        User::forceCreate([
            'full_name' => 'Staff Member', 'phone' => '+213555999888', 'email' => 'staff@chaba.dz',
            'password_hash' => bcrypt('password123'), 'role_id' => $role->id, 'status' => 'active',
        ]);

        $this->expectException(ApiException::class);

        try {
            $this->service()->resolve('google', 'g-staff', 'staff@chaba.dz', true, 'Staff Member');
        } finally {
            $this->assertSame(0, SocialAccount::count());
        }
    }

    public function test_a_blocked_account_is_refused_even_via_a_matching_social_account(): void
    {
        $user = User::forceCreate([
            'full_name' => 'Blocked User', 'phone' => '+213555222999', 'email' => 'blocked@example.com',
            'password_hash' => bcrypt('password123'), 'status' => 'blocked',
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-blocked', 'provider_email' => $user->email]);

        $this->expectException(ApiException::class);
        $this->service()->resolve('google', 'g-blocked', 'blocked@example.com', true, 'Blocked User');
    }

    public function test_a_two_factor_enabled_account_gets_a_pending_token_not_a_full_pair(): void
    {
        $user = User::create([
            'full_name' => '2FA User', 'phone' => '+213555333777', 'email' => 'twofactor@example.com',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now(),
        ]);
        SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-2fa', 'provider_email' => $user->email]);

        $payload = $this->service()->resolve('google', 'g-2fa', 'twofactor@example.com', true, '2FA User');

        $this->assertTrue($payload['requires_two_factor']);
        $this->assertArrayHasKey('two_factor_token', $payload);
        $this->assertArrayNotHasKey('access_token', $payload);
    }
}

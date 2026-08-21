<?php

namespace Tests\Feature\Auth;

use App\Events\OtpGenerated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    public function test_password_can_be_reset_end_to_end(): void
    {
        Event::fake([OtpGenerated::class]);

        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555555666',
            'password_hash' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '0555555666',
            'purpose' => 'password_reset',
        ])->assertStatus(200);

        $code = null;
        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) use (&$code) {
            $code = $event->code;

            return $event->purpose === 'password_reset';
        });

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '0555555666',
            'code' => $code,
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(200);

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password_hash));

        // Old sessions are invalidated on password change.
        $this->postJson('/api/v1/auth/login', [
            'login' => '0555555666',
            'password' => 'old-password',
        ])->assertStatus(401);

        $this->postJson('/api/v1/auth/login', [
            'login' => '0555555666',
            'password' => 'new-password-123',
        ])->assertStatus(200);
    }

    public function test_password_reset_rejects_a_wrong_code(): void
    {
        Event::fake([OtpGenerated::class]);

        User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555555667',
            'password_hash' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '0555555667',
            'purpose' => 'password_reset',
        ]);

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '0555555667',
            'code' => '000000',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(400)->assertJsonPath('error.code', 'otp_invalid');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Events\OtpGenerated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    private function capturedCode(string $phone, string $purpose): string
    {
        $code = null;
        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) use ($phone, $purpose, &$code) {
            if ($event->phone === $phone && $event->purpose === $purpose) {
                $code = $event->code;

                return true;
            }

            return false;
        });

        return $code;
    }

    public function test_send_then_verify_marks_the_phone_verified(): void
    {
        Event::fake([OtpGenerated::class]);

        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555444555',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '0555444555',
            'purpose' => 'verify',
        ])->assertStatus(200);

        $code = $this->capturedCode('+213555444555', 'verify');

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '0555444555',
            'purpose' => 'verify',
            'code' => $code,
        ])->assertStatus(200)->assertJsonPath('data.user.phone_verified', true);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_otp_login_issues_tokens_for_an_existing_user(): void
    {
        Event::fake([OtpGenerated::class]);

        User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555444556',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '0555444556',
            'purpose' => 'login',
        ])->assertStatus(200);

        $code = $this->capturedCode('+213555444556', 'login');

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '0555444556',
            'purpose' => 'login',
            'code' => $code,
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_otp_send_for_login_fails_silently_for_an_unregistered_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '0555444557',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'otp_send_failed');
    }

    public function test_wrong_code_is_rejected_and_correct_code_still_works_before_lockout(): void
    {
        Event::fake([OtpGenerated::class]);

        User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555444558',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', ['phone' => '0555444558', 'purpose' => 'verify']);
        $code = $this->capturedCode('+213555444558', 'verify');

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '0555444558', 'purpose' => 'verify', 'code' => '000000',
        ])->assertStatus(400)->assertJsonPath('error.code', 'otp_invalid');

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '0555444558', 'purpose' => 'verify', 'code' => $code,
        ])->assertStatus(200);
    }

    public function test_five_wrong_attempts_locks_out_further_verification(): void
    {
        Event::fake([OtpGenerated::class]);

        User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555444559',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', ['phone' => '0555444559', 'purpose' => 'verify']);
        $code = $this->capturedCode('+213555444559', 'verify');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/otp/verify', [
                'phone' => '0555444559', 'purpose' => 'verify', 'code' => '000000',
            ]);
        }

        // Even the real code is now rejected — locked out, not just "wrong code".
        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '0555444559', 'purpose' => 'verify', 'code' => $code,
        ])->assertStatus(429)->assertJsonPath('error.code', 'otp_locked');
    }

    public function test_requesting_a_second_code_within_a_minute_is_rate_limited(): void
    {
        Event::fake([OtpGenerated::class]);

        User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555444560',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/send', ['phone' => '0555444560', 'purpose' => 'verify'])
            ->assertStatus(200);

        $this->postJson('/api/v1/auth/otp/send', ['phone' => '0555444560', 'purpose' => 'verify'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited');
    }
}

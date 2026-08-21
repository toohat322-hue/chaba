<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_with_a_valid_algerian_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0555111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.phone', '+213555111222')
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);

        $this->assertDatabaseHas('users', ['phone' => '+213555111222']);

        $user = User::where('phone', '+213555111222')->first();
        $this->assertTrue(Hash::check('password123', $user->password_hash));
    }

    public function test_registering_with_an_email_sends_a_welcome_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0555111222',
            'email' => 'amina@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'preferred_language' => 'fr',
        ])->assertStatus(201);

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) {
            return $mail->hasTo('amina@example.com') && $mail->user->preferred_language === 'fr';
        });
    }

    public function test_registering_without_an_email_sends_no_welcome_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0555111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        Mail::assertNothingSent();
    }

    public function test_registration_rejects_a_duplicate_phone(): void
    {
        User::create([
            'full_name' => 'Existing',
            'phone' => '+213555111222',
            'password_hash' => bcrypt('whatever'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0555111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.field_errors.phone.0', 'The phone has already been taken.');
    }

    public function test_registration_rejects_an_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0123456789', // starts with 01, not a valid mobile prefix
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(400)->assertJsonPath('error.code', 'validation_error');
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'full_name' => 'Amina Test',
            'phone' => '0555111222',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(400)->assertJsonPath('error.code', 'validation_error');
    }
}

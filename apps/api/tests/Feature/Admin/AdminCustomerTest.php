<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_list_only_includes_customers_not_staff(): void
    {
        [, $headers] = $this->actingAsRole('Customer Support');

        User::create([
            'full_name' => 'A Customer', 'phone' => '+213555111000',
            'password_hash' => Hash::make('x'), 'status' => 'active',
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/customers');

        $response->assertStatus(200);
        $names = collect($response->json('data.items'))->pluck('full_name');
        $this->assertTrue($names->contains('A Customer'));
        $this->assertFalse($names->contains('Customer Support Test User'));
    }

    public function test_blocking_a_customer_prevents_them_from_logging_in(): void
    {
        [, $headers] = $this->actingAsRole('Customer Support');

        $customer = User::create([
            'full_name' => 'Blockable', 'phone' => '+213555111001',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);

        $this->withHeaders($headers)->patchJson("/api/v1/admin/customers/{$customer->id}", [
            'status' => 'blocked',
        ])->assertStatus(200)->assertJsonPath('data.status', 'blocked');

        $this->postJson('/api/v1/auth/login', [
            'login' => '0555111001',
            'password' => 'password123',
        ])->assertStatus(403)->assertJsonPath('error.code', 'account_blocked');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_a_coupon_can_be_created_updated_and_deactivated(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/coupons', [
            'code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10,
            'usage_limit_total' => 100, 'usage_limit_per_customer' => 1,
        ]);
        $create->assertStatus(201)
            ->assertJsonPath('data.code', 'WELCOME10')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.usage_count', 0);

        $couponId = $create->json('data.id');

        $update = $this->withHeaders($headers)->patchJson("/api/v1/admin/coupons/{$couponId}", ['value' => 15]);
        $update->assertStatus(200)->assertJsonPath('data.value', 15);

        $deactivate = $this->withHeaders($headers)->deleteJson("/api/v1/admin/coupons/{$couponId}");
        $deactivate->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $couponId, 'is_active' => false]);
    }

    public function test_a_duplicate_coupon_code_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        Coupon::create(['code' => 'DUP', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);

        $this->withHeaders($headers)->postJson('/api/v1/admin/coupons', [
            'code' => 'DUP', 'type' => 'fixed', 'value' => 5000,
        ])->assertStatus(400);
    }

    public function test_a_percentage_value_over_100_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $this->withHeaders($headers)->postJson('/api/v1/admin/coupons', [
            'code' => 'TOOBIG', 'type' => 'percentage', 'value' => 150,
        ])->assertStatus(400);
    }

    public function test_an_end_date_before_the_start_date_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $this->withHeaders($headers)->postJson('/api/v1/admin/coupons', [
            'code' => 'BADDATES', 'type' => 'fixed', 'value' => 5000,
            'start_date' => '2026-06-01', 'end_date' => '2026-05-01',
        ])->assertStatus(400);
    }

    public function test_a_role_without_coupons_permission_is_forbidden(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/coupons')->assertStatus(403);
    }
}

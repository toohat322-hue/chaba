<?php

namespace Tests\Feature;

use App\Models\DeliveryFee;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class DeliveryFeeTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    private function makeWilaya(): Wilaya
    {
        return Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
    }

    public function test_public_lookup_returns_null_home_fee_when_unset(): void
    {
        $this->makeWilaya();

        $response = $this->getJson('/api/v1/delivery-fees/16');

        $response->assertStatus(200)
            ->assertJsonPath('data.wilaya_code', '16')
            ->assertJsonPath('data.home', null)
            ->assertJsonPath('data.pickup', null);
    }

    public function test_public_lookup_reflects_a_configured_fee(): void
    {
        $wilaya = $this->makeWilaya();
        DeliveryFee::create(['wilaya_code' => $wilaya->code, 'delivery_method' => 'home', 'fee' => 50000]);

        $this->getJson('/api/v1/delivery-fees/16')
            ->assertStatus(200)
            ->assertJsonPath('data.home.fee', 50000);
    }

    public function test_public_lookup_reflects_a_configured_pickup_fee(): void
    {
        $wilaya = $this->makeWilaya();
        DeliveryFee::create(['wilaya_code' => $wilaya->code, 'delivery_method' => 'pickup', 'fee' => 40000]);

        $this->getJson('/api/v1/delivery-fees/16')
            ->assertStatus(200)
            ->assertJsonPath('data.pickup.fee', 40000)
            ->assertJsonPath('data.home', null);
    }

    public function test_order_manager_can_update_a_wilayas_delivery_fee(): void
    {
        $this->seedRbac();
        $this->makeWilaya();
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->patchJson('/api/v1/admin/delivery-fees/16', [
            'fee' => 60000,
            'pickup_fee' => 45000,
            'eta_min_days' => 1,
            'eta_max_days' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.fee', 60000)
            ->assertJsonPath('data.pickup_fee', 45000);
        $this->getJson('/api/v1/delivery-fees/16')
            ->assertJsonPath('data.home.fee', 60000)
            ->assertJsonPath('data.pickup.fee', 45000);
    }

    public function test_a_role_without_delivery_edit_is_forbidden(): void
    {
        $this->seedRbac();
        $this->makeWilaya();
        [, $headers] = $this->actingAsRole('Product Manager');

        $this->withHeaders($headers)->patchJson('/api/v1/admin/delivery-fees/16', ['fee' => 60000])
            ->assertStatus(403);
    }
}

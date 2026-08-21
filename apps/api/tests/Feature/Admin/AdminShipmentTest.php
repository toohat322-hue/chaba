<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminShipmentTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    private function makeOrder(): Order
    {
        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);
        $address = Address::create([
            'full_name' => 'Amina Test', 'phone' => '0555222333',
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue 1',
        ]);

        return Order::create([
            'order_number' => 'CHB-2026-'.random_int(100000, 999999),
            'guest_name' => 'Amina Test',
            'guest_phone' => '0555222333',
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);
    }

    public function test_order_manager_can_create_a_shipment_with_courier_details(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $response = $this->withHeaders($headers)->postJson("/api/v1/admin/orders/{$order->id}/shipments", [
            'courier_partner' => 'Yalidine',
            'tracking_number' => 'YAL-123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.courier_partner', 'Yalidine')
            ->assertJsonPath('data.tracking_number', 'YAL-123456');

        $this->assertDatabaseHas('shipments', ['order_id' => $order->id, 'courier_partner' => 'Yalidine']);
    }

    public function test_a_shipment_can_be_created_with_no_courier_details_yet(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $response = $this->withHeaders($headers)->postJson("/api/v1/admin/orders/{$order->id}/shipments", []);

        $response->assertStatus(201)->assertJsonPath('data.status', 'pending');
    }

    public function test_shipment_status_progresses_through_the_full_happy_path(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $shipmentId = $this->withHeaders($headers)
            ->postJson("/api/v1/admin/orders/{$order->id}/shipments", ['courier_partner' => 'Yalidine'])
            ->json('data.id');

        foreach (['picked_up', 'in_transit', 'out_for_delivery', 'delivered'] as $status) {
            $this->withHeaders($headers)
                ->patchJson("/api/v1/admin/shipments/{$shipmentId}/status", ['status' => $status])
                ->assertStatus(200)
                ->assertJsonPath('data.status', $status);
        }

        $this->assertNotNull(Shipment::find($shipmentId)->delivered_at);
    }

    public function test_an_invalid_shipment_status_transition_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $shipmentId = $this->withHeaders($headers)
            ->postJson("/api/v1/admin/orders/{$order->id}/shipments", [])
            ->json('data.id');

        // Can't jump straight from pending to delivered.
        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/shipments/{$shipmentId}/status", ['status' => 'delivered'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_shipment_status_transition');
    }

    public function test_a_failed_shipment_can_be_retried_or_returned(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $shipmentId = $this->withHeaders($headers)
            ->postJson("/api/v1/admin/orders/{$order->id}/shipments", [])
            ->json('data.id');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/shipments/{$shipmentId}/status", [
            'status' => 'failed', 'failure_reason' => 'Customer unreachable',
        ])->assertStatus(200)->assertJsonPath('data.failure_reason', 'Customer unreachable');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/shipments/{$shipmentId}/status", ['status' => 'returned'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'returned');
    }

    public function test_the_order_response_includes_its_shipments(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');
        $order = $this->makeOrder();

        $this->withHeaders($headers)->postJson("/api/v1/admin/orders/{$order->id}/shipments", [
            'courier_partner' => 'Yalidine',
        ]);

        $this->withHeaders($headers)->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.shipments.0.courier_partner', 'Yalidine');
    }

    public function test_a_role_without_delivery_edit_cannot_create_a_shipment(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $order = $this->makeOrder();

        $this->withHeaders($headers)
            ->postJson("/api/v1/admin/orders/{$order->id}/shipments", [])
            ->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $orderNumber, string $phone): Order
    {
        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre']);
        $address = Address::create([
            'full_name' => 'Amina Test', 'phone' => $phone,
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue Test',
        ]);

        return Order::create([
            'order_number' => $orderNumber,
            'guest_name' => 'Amina Test', 'guest_phone' => $phone, 'guest_email' => null,
            'address_id' => $address->id,
            'delivery_method' => 'home', 'payment_method' => 'cod',
            'payment_status' => 'pending', 'order_status' => 'pending',
            'subtotal' => 100000, 'discount_total' => 0, 'delivery_fee' => 0, 'tax_total' => 0, 'grand_total' => 100000,
        ]);
    }

    public function test_the_correct_order_number_and_phone_returns_the_order(): void
    {
        $this->makeOrder('CHB-2026-000123', '+213555222333');

        $response = $this->getJson('/api/v1/orders/track?order_number=CHB-2026-000123&phone=0555222333');

        $response->assertStatus(200)->assertJsonPath('data.order_number', 'CHB-2026-000123');
    }

    public function test_a_mismatched_phone_returns_not_found_without_leaking_the_order(): void
    {
        $this->makeOrder('CHB-2026-000123', '+213555222333');

        $this->getJson('/api/v1/orders/track?order_number=CHB-2026-000123&phone=0555999888')
            ->assertStatus(404);
    }

    public function test_an_unknown_order_number_returns_not_found(): void
    {
        $this->getJson('/api/v1/orders/track?order_number=CHB-2026-999999&phone=0555222333')
            ->assertStatus(404);
    }
}

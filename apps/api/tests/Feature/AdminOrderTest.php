<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    /** @return array{0: Order, 1: ProductVariant} */
    private function makeOrder(int $stock = 10, int $quantity = 3, string $status = 'pending', string $paymentMethod = 'cod'): array
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => $stock, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        $wilaya = Wilaya::firstOrCreate(['code' => '16'], ['name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::firstOrCreate(
            ['wilaya_code' => $wilaya->code],
            ['name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre'],
        );
        $address = Address::create([
            'full_name' => 'Amina Test', 'phone' => '+213555222333',
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue Test',
        ]);

        $order = Order::create([
            'order_number' => 'CHB-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'guest_name' => 'Amina Test', 'guest_phone' => '+213555222333', 'guest_email' => null,
            'address_id' => $address->id,
            'delivery_method' => 'home', 'payment_method' => $paymentMethod,
            'payment_status' => 'pending', 'order_status' => $status,
            'subtotal' => 100000 * $quantity, 'discount_total' => 0, 'delivery_fee' => 0, 'tax_total' => 0,
            'grand_total' => 100000 * $quantity,
        ]);
        $order->items()->create([
            'variant_id' => $variant->id, 'product_name_snapshot' => 'Product', 'sku_snapshot' => $variant->sku,
            'unit_price' => 100000, 'quantity' => $quantity, 'line_total' => 100000 * $quantity,
        ]);
        $order->statusHistory()->create(['status' => $status, 'note' => null, 'actor_id' => null, 'created_at' => now()]);

        return [$order, $variant];
    }

    public function test_a_customer_without_a_role_is_forbidden_from_admin_orders(): void
    {
        $this->seedRbac();
        $this->makeOrder();
        [, $headers] = $this->actingAsCustomer();

        $this->withHeaders($headers)->getJson('/api/v1/admin/orders')->assertStatus(403);
    }

    public function test_a_role_without_orders_view_is_forbidden(): void
    {
        $this->seedRbac();
        $this->makeOrder();
        [, $headers] = $this->actingAsRole('Product Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/orders')->assertStatus(403);
    }

    public function test_order_manager_can_list_and_filter_orders_by_status(): void
    {
        $this->seedRbac();
        $this->makeOrder(status: 'pending');
        $this->makeOrder(status: 'confirmed');
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/orders?status=confirmed');

        $response->assertStatus(200)->assertJsonCount(1, 'data.items');
        $this->assertSame('confirmed', $response->json('data.items.0.order_status'));
    }

    public function test_an_illegal_status_transition_is_rejected(): void
    {
        $this->seedRbac();
        [$order] = $this->makeOrder(status: 'pending');
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'invalid_status_transition');
    }

    public function test_cancelling_an_order_restores_stock_and_logs_history(): void
    {
        $this->seedRbac();
        [$order, $variant] = $this->makeOrder(stock: 5, quantity: 3, status: 'pending');
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'cancelled',
            'note' => 'Customer changed their mind',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'cod_pending_collection');

        $this->assertSame(8, Inventory::where('variant_id', $variant->id)->value('stock_quantity'));

        $history = $this->withHeaders($headers)->getJson("/api/v1/admin/orders/{$order->id}")->json('data.status_history');
        $this->assertCount(2, $history);
        $this->assertSame('cancelled', $history[1]['status']);
    }

    public function test_a_terminal_status_cannot_be_transitioned_further(): void
    {
        $this->seedRbac();
        [$order] = $this->makeOrder(status: 'cancelled');
        [, $headers] = $this->actingAsRole('Order Manager');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertStatus(422);
    }

    public function test_a_delivered_cod_order_flips_payment_status_to_collected(): void
    {
        $this->seedRbac();
        [$order] = $this->makeOrder(status: 'out_for_delivery');
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'delivered')
            ->assertJsonPath('data.payment_status', 'cod_collected');
    }

    public function test_admin_can_see_a_whatsapp_orders_payment_method_and_status(): void
    {
        $this->seedRbac();
        [$order] = $this->makeOrder(paymentMethod: 'whatsapp');
        $order->update(['whatsapp_status' => 'opened']);
        [, $headers] = $this->actingAsRole('Order Manager');

        $response = $this->withHeaders($headers)->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_method', 'whatsapp')
            ->assertJsonPath('data.whatsapp_status', 'opened');
    }
}

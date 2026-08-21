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
use Tests\TestCase;

class CheckoutWhatsAppOpenedTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $phone = '+213555222333'): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre']);
        $address = Address::create([
            'full_name' => 'Amina Test', 'phone' => $phone,
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue des Fleurs 12',
        ]);

        return Order::create([
            'order_number' => 'CHB-2026-000001',
            'guest_name' => 'Amina Test',
            'guest_phone' => $phone,
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => 'whatsapp',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'whatsapp_status' => 'pending',
        ]);
    }

    public function test_marks_whatsapp_status_opened_when_the_phone_matches(): void
    {
        $order = $this->makeOrder('+213555222333');

        $response = $this->postJson("/api/v1/checkout/{$order->order_number}/whatsapp-opened", [
            'phone' => '0555222333',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.whatsapp_status', 'opened');
        $this->assertSame('opened', $order->fresh()->whatsapp_status);
    }

    public function test_does_not_leak_or_update_when_the_phone_does_not_match(): void
    {
        $order = $this->makeOrder('+213555222333');

        $response = $this->postJson("/api/v1/checkout/{$order->order_number}/whatsapp-opened", [
            'phone' => '0555999888',
        ]);

        $response->assertStatus(404);
        $this->assertSame('pending', $order->fresh()->whatsapp_status);
    }
}

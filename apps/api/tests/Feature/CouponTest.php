<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\DeliveryFee;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(int $stock = 10, int $price = 100000): ProductVariant
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => $price,
            'status' => 'active',
        ]);

        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);

        Inventory::create([
            'variant_id' => $variant->id, 'stock_quantity' => $stock,
            'reserved_quantity' => 0, 'low_stock_threshold' => 5,
        ]);

        return $variant;
    }

    private function makeCommune(): Commune
    {
        $wilaya = Wilaya::firstOrCreate(['code' => '16'], ['name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);

        return Commune::firstOrCreate(
            ['wilaya_code' => $wilaya->code],
            ['name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre'],
        );
    }

    private function guestHeader(string $token = 'coupon-session-1'): array
    {
        return ['X-Guest-Session' => $token];
    }

    private function checkoutPayload(Commune $commune): array
    {
        return [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => [
                'full_name' => 'Amina Test', 'phone' => '0555222333',
                'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
                'address_line' => 'Rue des Fleurs 12',
            ],
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ];
    }

    public function test_a_valid_percentage_coupon_can_be_applied_and_removed_from_the_cart(): void
    {
        $variant = $this->makeVariant(price: 100000);
        Coupon::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);

        $apply = $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'save10']);
        $apply->assertStatus(200)
            ->assertJsonPath('data.coupon.code', 'SAVE10')
            ->assertJsonPath('data.discount_total', 20000);

        $remove = $this->withHeaders($headers)->deleteJson('/api/v1/cart/coupon');
        $remove->assertStatus(200)->assertJsonPath('data.coupon', null)->assertJsonPath('data.discount_total', 0);
    }

    public function test_an_unknown_coupon_code_is_rejected(): void
    {
        $variant = $this->makeVariant();
        $headers = $this->guestHeader();
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'NOPE'])
            ->assertStatus(422)->assertJsonPath('error.code', 'coupon_not_found');
    }

    public function test_an_expired_coupon_is_rejected(): void
    {
        $variant = $this->makeVariant();
        Coupon::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 5000, 'end_date' => now()->subDay(), 'is_active' => true]);
        $headers = $this->guestHeader();
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'OLD'])
            ->assertStatus(422)->assertJsonPath('error.code', 'coupon_expired');
    }

    public function test_a_coupon_below_its_minimum_order_value_is_rejected(): void
    {
        $variant = $this->makeVariant(price: 50000);
        Coupon::create(['code' => 'BIGORDER', 'type' => 'fixed', 'value' => 5000, 'min_order_value' => 200000, 'is_active' => true]);
        $headers = $this->guestHeader();
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'BIGORDER'])
            ->assertStatus(422)->assertJsonPath('error.code', 'coupon_min_order_not_met');
    }

    public function test_checkout_applies_the_discount_and_records_usage(): void
    {
        $variant = $this->makeVariant(price: 100000);
        $commune = $this->makeCommune();
        $coupon = Coupon::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'SAVE10'])->assertStatus(200);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune));

        $response->assertStatus(201)
            ->assertJsonPath('data.order.subtotal', 200000)
            ->assertJsonPath('data.order.discount_total', 20000)
            ->assertJsonPath('data.order.grand_total', 180000)
            ->assertJsonPath('data.order.coupon.code', 'SAVE10');

        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
    }

    public function test_a_free_shipping_coupon_zeroes_the_delivery_fee(): void
    {
        $variant = $this->makeVariant(price: 100000);
        $commune = $this->makeCommune();
        DeliveryFee::create([
            'wilaya_code' => $commune->wilaya_code, 'delivery_method' => 'home', 'fee' => 40000,
        ]);
        Coupon::create(['code' => 'FREESHIP', 'type' => 'free_shipping', 'is_active' => true]);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'FREESHIP'])->assertStatus(200);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune));

        $response->assertStatus(201)
            ->assertJsonPath('data.order.delivery_fee', 0)
            ->assertJsonPath('data.order.discount_total', 0)
            ->assertJsonPath('data.order.grand_total', 100000);
    }

    public function test_a_coupon_past_its_total_usage_limit_is_rejected_when_applying_to_the_cart(): void
    {
        $variant = $this->makeVariant(price: 100000, stock: 10);
        $commune = $this->makeCommune();
        $coupon = Coupon::create(['code' => 'ONEUSE', 'type' => 'fixed', 'value' => 10000, 'usage_limit_total' => 1, 'is_active' => true]);

        $firstOrder = Order::create([
            'order_number' => 'CHB-2026-000001', 'guest_name' => 'A', 'guest_phone' => '+213555000000',
            'address_id' => Address::create([
                'full_name' => 'A', 'phone' => '+213555000000',
                'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id, 'address_line' => 'x',
            ])->id,
            'delivery_method' => 'home', 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending',
            'subtotal' => 100000, 'discount_total' => 10000, 'delivery_fee' => 0, 'tax_total' => 0, 'grand_total' => 90000,
            'coupon_id' => $coupon->id,
        ]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $firstOrder->id, 'used_at' => now()]);

        $headers = $this->guestHeader();
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $applyAttempt = $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'ONEUSE']);
        $applyAttempt->assertStatus(409)->assertJsonPath('error.code', 'coupon_usage_limit_reached');
    }

    public function test_checkout_re_validates_and_rejects_a_coupon_used_up_after_it_was_applied_to_the_cart(): void
    {
        $variant = $this->makeVariant(price: 100000);
        $commune = $this->makeCommune();
        $coupon = Coupon::create(['code' => 'RACEME', 'type' => 'fixed', 'value' => 10000, 'usage_limit_total' => 1, 'is_active' => true]);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'RACEME'])->assertStatus(200);

        // Simulate a concurrent order consuming the coupon's only remaining
        // use between cart-apply and this checkout — same technique as
        // CheckoutTest's stock-race test (mutate state directly, then
        // check the checkout-time authoritative re-validation catches it).
        $otherOrder = Order::create([
            'order_number' => 'CHB-2026-000002', 'guest_name' => 'B', 'guest_phone' => '+213555999888',
            'address_id' => Address::create([
                'full_name' => 'B', 'phone' => '+213555999888',
                'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id, 'address_line' => 'y',
            ])->id,
            'delivery_method' => 'home', 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending',
            'subtotal' => 100000, 'discount_total' => 10000, 'delivery_fee' => 0, 'tax_total' => 0, 'grand_total' => 90000,
            'coupon_id' => $coupon->id,
        ]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $otherOrder->id, 'used_at' => now()]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune));

        $response->assertStatus(409)->assertJsonPath('error.code', 'coupon_usage_limit_reached');
        $this->assertNull(Order::where('guest_phone', '+213555222333')->first());
    }
}

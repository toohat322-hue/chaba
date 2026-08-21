<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Mail\OrderConfirmedMail;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Commune;
use App\Models\DeliveryFee;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Wilaya;
use App\Services\OrderService;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::random(8),
        ]);

        Inventory::create([
            'variant_id' => $variant->id,
            'stock_quantity' => $stock,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

        return $variant;
    }

    private function makeCommune(): Commune
    {
        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        DeliveryFee::create(['wilaya_code' => $wilaya->code, 'delivery_method' => 'home']);

        return Commune::create([
            'wilaya_code' => $wilaya->code,
            'name_ar' => 'الجزائر الوسطى', 'name_fr' => 'Alger Centre', 'name_en' => 'Algiers Centre',
        ]);
    }

    private function guestHeader(string $token = 'checkout-session-1'): array
    {
        return ['X-Guest-Session' => $token];
    }

    private function inlineAddress(Commune $commune, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Amina Test',
            'phone' => '0555222333',
            'wilaya_code' => $commune->wilaya_code,
            'commune_id' => $commune->id,
            'address_line' => 'Rue des Fleurs 12',
        ], $overrides);
    }

    public function test_guest_checkout_succeeds_end_to_end(): void
    {
        $variant = $this->makeVariant(stock: 10, price: 100000);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertStatus(201);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
            'locale' => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order.order_status', 'pending')
            ->assertJsonPath('data.order.payment_method', 'cod')
            ->assertJsonPath('data.order.subtotal', 200000)
            ->assertJsonPath('data.order.items.0.quantity', 2)
            ->assertJsonPath('data.whatsapp', null);

        $this->assertMatchesRegularExpression('/^CHB-\d{4}-\d{6}$/', $response->json('data.order.order_number'));
    }

    public function test_checkout_commits_stock_and_converts_the_cart(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3]);

        $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ])->assertStatus(201);

        $inventory = Inventory::where('variant_id', $variant->id)->first();
        $this->assertSame(7, $inventory->stock_quantity);
        $this->assertSame(0, $inventory->reserved_quantity);

        $cart = Cart::where('session_token', 'checkout-session-1')->first();
        $this->assertSame('converted', $cart->status);

        // A fresh GET /cart auto-creates a new active cart — the converted
        // one is never reused.
        $newCart = $this->withHeaders($headers)->getJson('/api/v1/cart');
        $newCart->assertStatus(200)->assertJsonPath('data.item_count', 0);
    }

    public function test_checkout_snapshots_the_variant_size_and_a_later_size_edit_never_changes_it(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $variant->update(['size_value' => 50, 'size_unit' => 'ml']);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ])->assertStatus(201);

        $orderItem = OrderItem::where('variant_id', $variant->id)->first();
        $this->assertSame('50 ml', $orderItem->size_snapshot);

        // Admin later renames the size — the already-placed order must not change.
        $variant->update(['size_value' => 100, 'size_unit' => 'ml']);

        $this->assertSame('50 ml', $orderItem->fresh()->size_snapshot);
    }

    public function test_checkout_with_an_empty_cart_is_rejected(): void
    {
        $commune = $this->makeCommune();

        $response = $this->withHeaders($this->guestHeader())->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'cart_empty');
    }

    public function test_checkout_rejects_when_a_race_left_the_reservation_stale(): void
    {
        $variant = $this->makeVariant(stock: 5);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 5])
            ->assertStatus(201);

        // Simulate an admin correction dropping stock after the reservation
        // was made — reserved_quantity (5) now exceeds real stock (2).
        Inventory::where('variant_id', $variant->id)->update(['stock_quantity' => 2]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'insufficient_stock');
    }

    public function test_authenticated_checkout_with_a_saved_address_succeeds(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();

        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);
        $headers = ['Authorization' => "Bearer {$pair['access_token']}"];

        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'Amina Test',
            'phone' => '+213555222333',
            'wilaya_code' => $commune->wilaya_code,
            'commune_id' => $commune->id,
            'address_line' => 'Rue des Fleurs 12',
            'is_default' => true,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.order.address.address_line', 'Rue des Fleurs 12');
        $this->assertSame($user->id, Order::first()->user_id);
    }

    public function test_order_confirmation_emails_the_registered_users_account_email(): void
    {
        Mail::fake();

        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();

        $user = User::create([
            'full_name' => 'Amina Test',
            'email' => 'amina@example.com',
            'phone' => '+213555222334',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);
        $headers = ['Authorization' => "Bearer {$pair['access_token']}"];

        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => 'Amina Test',
            'phone' => '+213555222334',
            'wilaya_code' => $commune->wilaya_code,
            'commune_id' => $commune->id,
            'address_line' => 'Rue des Fleurs 12',
            'is_default' => true,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222334',
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ])->assertStatus(201);

        Mail::assertSent(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) {
            return $mail->hasTo('amina@example.com');
        });
    }

    public function test_checkout_rejects_an_address_id_belonging_to_another_customer(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();

        $owner = User::create([
            'full_name' => 'Owner', 'phone' => '+213555111111',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
        $address = Address::create([
            'user_id' => $owner->id,
            'full_name' => 'Owner', 'phone' => '+213555111111',
            'wilaya_code' => $commune->wilaya_code, 'commune_id' => $commune->id,
            'address_line' => 'Owner street',
        ]);

        $stranger = User::create([
            'full_name' => 'Stranger', 'phone' => '+213555222222',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($stranger);
        $headers = ['Authorization' => "Bearer {$pair['access_token']}"];

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Stranger',
            'customer_phone' => '0555222222',
            'address_id' => $address->id,
            'delivery_method' => 'home',
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(404);
    }

    private function enableWhatsAppOrders(string $number = '213555111222'): void
    {
        StoreSetting::current()->update([
            'whatsapp_number' => $number,
            'whatsapp_active' => true,
            'whatsapp_orders_enabled' => true,
        ]);
    }

    public function test_whatsapp_checkout_returns_an_order_and_a_wa_me_url(): void
    {
        $this->enableWhatsAppOrders();
        $variant = $this->makeVariant(stock: 10, price: 100000);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'whatsapp',
            'locale' => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order.payment_method', 'whatsapp')
            ->assertJsonPath('data.order.whatsapp_status', 'pending')
            ->assertJsonPath('data.whatsapp.phone', '213555111222');

        $url = $response->json('data.whatsapp.url');
        $this->assertStringStartsWith('https://wa.me/213555111222?text=', $url);

        $message = $response->json('data.whatsapp.message');
        $this->assertStringContainsString($response->json('data.order.order_number'), $message);
    }

    public function test_whatsapp_checkout_is_rejected_when_orders_are_disabled(): void
    {
        StoreSetting::current()->update([
            'whatsapp_number' => '213555111222', 'whatsapp_active' => true, 'whatsapp_orders_enabled' => false,
        ]);
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'whatsapp',
        ]);

        // This app's exception handler maps ValidationException to 400, not
        // Laravel's default 422 (see bootstrap/app.php) — matching the
        // existing disabled-gateway precedent in PaymentGatewayTest.
        $response->assertStatus(400)->assertJsonPath('error.code', 'validation_error');
        $this->assertArrayHasKey('payment_method', $response->json('error.field_errors'));
    }

    public function test_whatsapp_checkout_is_rejected_when_the_number_is_blank(): void
    {
        StoreSetting::current()->update([
            'whatsapp_number' => null, 'whatsapp_active' => true, 'whatsapp_orders_enabled' => true,
        ]);
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'whatsapp',
        ]);

        // This app's exception handler maps ValidationException to 400, not
        // Laravel's default 422 (see bootstrap/app.php) — matching the
        // existing disabled-gateway precedent in PaymentGatewayTest.
        $response->assertStatus(400)->assertJsonPath('error.code', 'validation_error');
        $this->assertArrayHasKey('payment_method', $response->json('error.field_errors'));
    }

    public function test_whatsapp_message_includes_every_line_item_with_size_and_quantity(): void
    {
        $this->enableWhatsAppOrders();
        $variantA = $this->makeVariant(stock: 10, price: 150000);
        $variantA->update(['size_value' => 10, 'size_unit' => 'ml']);
        $variantB = $this->makeVariant(stock: 10, price: 175000);
        $variantB->update(['size_value' => 50, 'size_unit' => 'ml']);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variantA->id, 'quantity' => 1]);
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variantB->id, 'quantity' => 2]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'whatsapp',
            'locale' => 'en',
        ])->assertStatus(201);

        $message = $response->json('data.whatsapp.message');
        $this->assertStringContainsString('10 ml', $message);
        $this->assertStringContainsString('50 ml', $message);
        $this->assertStringContainsString('Qty: 1', $message);
        $this->assertStringContainsString('Qty: 2', $message);
    }

    public function test_concurrent_checkout_requests_on_the_same_cart_do_not_create_two_orders(): void
    {
        // A sequential HTTP round-trip can't reproduce this race: once the
        // first request's response returns, the cart is already 'converted'
        // and a second HTTP call would simply resolve a fresh new cart (see
        // test_checkout_commits_stock_and_converts_the_cart above) — not
        // reproducing a double-click. The real race is two requests that
        // both resolved the *same* still-active Cart before either
        // committed, which is simulated here by driving OrderService
        // directly with one shared $cart instance, the same technique the
        // stale-reservation test above uses to simulate a race outcome
        // without real concurrent connections.
        $variant = $this->makeVariant(stock: 10);
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertStatus(201);

        $cart = Cart::where('session_token', 'checkout-session-1')->firstOrFail();

        $data = [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => $this->inlineAddress($commune),
            'delivery_method' => 'home',
            'payment_method' => 'cod',
            'locale' => 'en',
        ];

        $orders = app(OrderService::class);

        $orders->checkout($cart, $data, null);

        try {
            $orders->checkout($cart, $data, null);
            $this->fail('Expected the second checkout on the same cart to be rejected.');
        } catch (ApiException $exception) {
            $this->assertSame('cart_already_converted', $exception->errorCode);
            $this->assertSame(409, $exception->status);
        }

        $this->assertSame(1, Order::count());
    }
}

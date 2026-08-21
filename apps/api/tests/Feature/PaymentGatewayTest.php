<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\Category;
use App\Models\Commune;
use App\Models\DeliveryFee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wilaya;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\PaymentProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
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

    private function guestHeader(string $token = 'payment-gateway-session'): array
    {
        return ['X-Guest-Session' => $token];
    }

    private function checkoutPayload(Commune $commune, string $paymentMethod): array
    {
        return [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'address' => [
                'full_name' => 'Amina Test',
                'phone' => '0555222333',
                'wilaya_code' => $commune->wilaya_code,
                'commune_id' => $commune->id,
                'address_line' => 'Rue des Fleurs 12',
            ],
            'delivery_method' => 'home',
            'payment_method' => $paymentMethod,
        ];
    }

    public function test_checkout_rejects_cib_when_the_gateway_is_disabled(): void
    {
        config(['services.cib.enabled' => false]);

        $variant = $this->makeVariant();
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune, 'cib'));

        // This app's exception handler maps ValidationException to 400, not
        // Laravel's default 422 (see bootstrap/app.php) — matching that here.
        $response->assertStatus(400)->assertJsonPath('error.code', 'validation_error');
        $this->assertArrayHasKey('payment_method', $response->json('error.field_errors'));
    }

    public function test_checkout_fails_clearly_when_cib_is_enabled_but_missing_credentials(): void
    {
        config([
            'services.cib.enabled' => true,
            'services.cib.merchant_id' => null,
            'services.cib.api_key' => null,
            'services.cib.secret' => null,
        ]);

        $variant = $this->makeVariant();
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune, 'cib'));

        $response->assertStatus(422)->assertJsonPath('error.code', 'payment_gateway_not_configured');
    }

    public function test_checkout_fails_with_not_implemented_when_cib_is_fully_configured(): void
    {
        config([
            'services.cib.enabled' => true,
            'services.cib.merchant_id' => 'TEST-MERCHANT',
            'services.cib.api_key' => 'test-key',
            'services.cib.secret' => 'test-secret',
        ]);

        $variant = $this->makeVariant();
        $commune = $this->makeCommune();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/checkout', $this->checkoutPayload($commune, 'cib'));

        // Real gateway integration doesn't exist yet (no credentials/API docs
        // to build against) — this proves the abstraction wires through
        // cleanly to that honest failure, not a fabricated success.
        $response->assertStatus(422)->assertJsonPath('error.code', 'payment_gateway_not_implemented');
    }

    public function test_resolver_resolves_cod(): void
    {
        $resolver = app(PaymentGatewayResolver::class);

        $this->assertInstanceOf(PaymentProviderInterface::class, $resolver->resolve('cod'));
    }

    public function test_resolver_rejects_an_unknown_gateway_with_a_clear_error(): void
    {
        $resolver = app(PaymentGatewayResolver::class);

        try {
            $resolver->resolve('some-future-gateway');
            $this->fail('Expected an ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame('payment_method_unavailable', $exception->errorCode);
            $this->assertSame(422, $exception->status);
        }
    }
}

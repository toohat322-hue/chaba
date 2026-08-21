<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReviewReport;
use App\Models\User;
use App\Models\Wilaya;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
    }

    /** @return array{0: User, 1: array<string, string>} */
    private function makeCustomer(): array
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    private function makeDeliveredOrder(User $user, Product $product): void
    {
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);

        $wilaya = Wilaya::firstOrCreate(['code' => '16'], ['name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::firstOrCreate(
            ['wilaya_code' => $wilaya->code],
            ['name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre'],
        );
        $address = Address::create([
            'user_id' => $user->id,
            'full_name' => $user->full_name, 'phone' => $user->phone,
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue Test',
        ]);

        $order = Order::create([
            'order_number' => 'CHB-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'guest_name' => $user->full_name, 'guest_phone' => $user->phone, 'guest_email' => null,
            'address_id' => $address->id,
            'delivery_method' => 'home', 'payment_method' => 'cod',
            'payment_status' => 'cod_collected', 'order_status' => 'delivered',
            'subtotal' => 100000, 'discount_total' => 0, 'delivery_fee' => 0, 'tax_total' => 0, 'grand_total' => 100000,
        ]);
        $order->items()->create([
            'variant_id' => $variant->id, 'product_name_snapshot' => 'Product', 'sku_snapshot' => $variant->sku,
            'unit_price' => 100000, 'quantity' => 1, 'line_total' => 100000,
        ]);
    }

    public function test_a_verified_purchaser_review_is_auto_approved_with_a_badge(): void
    {
        $product = $this->makeProduct();
        [$user, $headers] = $this->makeCustomer();
        $this->makeDeliveredOrder($user, $product);

        $response = $this->withHeaders($headers)->postJson("/api/v1/products/{$product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Excellent',
            'body' => 'Loved it.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_verified_purchase', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertEquals(5.00, (float) $product->fresh()->avg_rating);
        $this->assertSame(1, $product->fresh()->review_count);
    }

    public function test_a_non_purchaser_review_is_pending_and_does_not_affect_the_average(): void
    {
        $product = $this->makeProduct();
        [, $headers] = $this->makeCustomer();

        $response = $this->withHeaders($headers)->postJson("/api/v1/products/{$product->slug}/reviews", [
            'rating' => 4,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_verified_purchase', false)
            ->assertJsonPath('data.status', 'pending');

        $this->assertSame(0, $product->fresh()->review_count);

        $publicList = $this->getJson("/api/v1/products/{$product->slug}/reviews");
        $publicList->assertJsonCount(0, 'data.items');
    }

    public function test_resubmitting_a_review_updates_instead_of_duplicating(): void
    {
        $product = $this->makeProduct();
        [$user, $headers] = $this->makeCustomer();
        $this->makeDeliveredOrder($user, $product);

        $this->withHeaders($headers)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 3])
            ->assertStatus(201);
        $this->withHeaders($headers)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertStatus(201);

        $this->assertSame(1, $product->fresh()->review_count);
        $this->assertEquals(5.00, (float) $product->fresh()->avg_rating);
    }

    public function test_average_rating_is_computed_from_approved_reviews_only(): void
    {
        $product = $this->makeProduct();

        [$userA, $headersA] = $this->makeCustomer();
        $this->makeDeliveredOrder($userA, $product);
        $this->withHeaders($headersA)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 4])
            ->assertStatus(201);

        [$userB, $headersB] = $this->makeCustomer();
        $this->makeDeliveredOrder($userB, $product);
        $this->withHeaders($headersB)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 2])
            ->assertStatus(201);

        $this->assertEquals(3.00, (float) $product->fresh()->avg_rating);
        $this->assertSame(2, $product->fresh()->review_count);
    }

    public function test_only_the_owner_can_edit_or_delete_their_review(): void
    {
        $product = $this->makeProduct();
        [$owner, $ownerHeaders] = $this->makeCustomer();
        $this->makeDeliveredOrder($owner, $product);

        $review = $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])
            ->json('data');

        [, $strangerHeaders] = $this->makeCustomer();

        $this->withHeaders($strangerHeaders)->patchJson("/api/v1/reviews/{$review['id']}", ['rating' => 1])
            ->assertStatus(404);
        $this->withHeaders($strangerHeaders)->deleteJson("/api/v1/reviews/{$review['id']}")
            ->assertStatus(404);

        $this->withHeaders($ownerHeaders)->patchJson("/api/v1/reviews/{$review['id']}", ['rating' => 4])
            ->assertStatus(200)
            ->assertJsonPath('data.rating', 4);
    }

    public function test_deleting_a_review_recalculates_the_average(): void
    {
        $product = $this->makeProduct();
        [$user, $headers] = $this->makeCustomer();
        $this->makeDeliveredOrder($user, $product);

        $review = $this->withHeaders($headers)
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])
            ->json('data');

        $this->withHeaders($headers)->deleteJson("/api/v1/reviews/{$review['id']}")->assertStatus(200);

        $this->assertSame(0, $product->fresh()->review_count);
        $this->assertEquals(0.00, (float) $product->fresh()->avg_rating);
    }

    public function test_reporting_a_review_creates_a_report(): void
    {
        $product = $this->makeProduct();
        [$author, $authorHeaders] = $this->makeCustomer();
        $this->makeDeliveredOrder($author, $product);

        $review = $this->withHeaders($authorHeaders)
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 1, 'body' => 'spam link here'])
            ->json('data');

        [, $reporterHeaders] = $this->makeCustomer();

        $response = $this->withHeaders($reporterHeaders)->postJson("/api/v1/reviews/{$review['id']}/report", [
            'reason' => 'Spam',
        ]);

        $response->assertStatus(201);
        $this->assertSame(1, ReviewReport::where('review_id', $review['id'])->count());
    }

    public function test_mine_endpoint_returns_the_customers_own_review_or_null(): void
    {
        $product = $this->makeProduct();
        [$user, $headers] = $this->makeCustomer();

        $this->withHeaders($headers)->getJson("/api/v1/products/{$product->slug}/reviews/mine")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->makeDeliveredOrder($user, $product);
        $this->withHeaders($headers)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5]);

        $this->withHeaders($headers)->getJson("/api/v1/products/{$product->slug}/reviews/mine")
            ->assertStatus(200)
            ->assertJsonPath('data.rating', 5);
    }

    public function test_review_endpoints_require_authentication(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])->assertStatus(401);
    }
}

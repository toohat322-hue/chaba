<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name = 'منتج'): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => $name, 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 100000,
            'status' => 'active',
        ]);

        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);

        return $product;
    }

    private function guestHeader(string $token = 'wishlist-session-1'): array
    {
        return ['X-Guest-Session' => $token];
    }

    public function test_a_guest_can_add_a_product_to_their_wishlist(): void
    {
        $product = $this->makeProduct();
        $headers = $this->guestHeader();

        $response = $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $product->id]);

        $response->assertStatus(201)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.product_slug', $product->slug)
            ->assertJsonPath('data.variant_id', $product->variants()->first()->id);

        // Regression: Wishlist has $timestamps = false (DB-level
        // useCurrent() default) — the freshly-created row's created_at must
        // still come back populated in this same response, not null.
        $this->assertNotNull($response->json('data.created_at'));

        $this->withHeaders($headers)->getJson('/api/v1/wishlist')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $product->id);
    }

    public function test_adding_the_same_product_twice_does_not_duplicate(): void
    {
        $product = $this->makeProduct();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $product->id])->assertStatus(201);
        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $product->id])->assertStatus(201);

        $this->withHeaders($headers)->getJson('/api/v1/wishlist')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_a_product_can_be_removed_from_the_wishlist(): void
    {
        $product = $this->makeProduct();
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $product->id]);
        $this->withHeaders($headers)->deleteJson("/api/v1/wishlist/{$product->id}")->assertStatus(200);

        $this->withHeaders($headers)->getJson('/api/v1/wishlist')->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_clear_empties_the_whole_wishlist(): void
    {
        $headers = $this->guestHeader();
        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $this->makeProduct()->id]);
        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $this->makeProduct()->id]);

        $this->withHeaders($headers)->deleteJson('/api/v1/wishlist')->assertStatus(200);

        $this->withHeaders($headers)->getJson('/api/v1/wishlist')->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_two_guest_sessions_have_isolated_wishlists(): void
    {
        $product = $this->makeProduct();

        $this->withHeaders($this->guestHeader('session-a'))
            ->postJson('/api/v1/wishlist', ['product_id' => $product->id])
            ->assertStatus(201);

        $this->withHeaders($this->guestHeader('session-b'))
            ->getJson('/api/v1/wishlist')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_an_authenticated_users_wishlist_is_scoped_to_their_account_not_a_session(): void
    {
        $product = $this->makeProduct();
        $user = User::create([
            'full_name' => 'Amina Test', 'phone' => '+213555222333',
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);
        $headers = ['Authorization' => "Bearer {$pair['access_token']}"];

        $this->withHeaders($headers)->postJson('/api/v1/wishlist', ['product_id' => $product->id])->assertStatus(201);

        // No X-Guest-Session header needed/used once authenticated.
        $this->withHeaders($headers)->getJson('/api/v1/wishlist')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_wishlist_endpoints_require_a_guest_session_header_when_unauthenticated(): void
    {
        $product = $this->makeProduct();

        $this->postJson('/api/v1/wishlist', ['product_id' => $product->id])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'guest_session_required');
    }

    public function test_adding_an_unknown_product_is_rejected(): void
    {
        $this->withHeaders($this->guestHeader())
            ->postJson('/api/v1/wishlist', ['product_id' => (string) Str::uuid()])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'validation_error');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cart-merge-on-login was a pre-existing gap for every login method (guest
 * carts and user carts are different DB rows, keyed differently) — the
 * social-login work closed it via one shared endpoint, so these tests cover
 * the general mechanism, not something social-specific.
 */
class CartMergeTest extends TestCase
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
            'slug' => 'product-'.Str::random(8), 'base_price' => $price, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => $stock, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        return $variant;
    }

    private function authHeaders(User $user): array
    {
        $pair = app(TokenService::class)->issuePair($user);

        return ['Authorization' => "Bearer {$pair['access_token']}"];
    }

    public function test_a_guest_carts_items_move_into_the_users_cart_on_merge(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $guestHeaders = ['X-Guest-Session' => 'merge-test-session'];
        $this->withHeaders($guestHeaders)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertStatus(201);

        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        $headers = $this->authHeaders($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/cart/merge', ['guest_session_token' => 'merge-test-session']);

        $response->assertStatus(200)->assertJsonPath('data.item_count', 2);

        // The guest cart is drained, not just copied — its own reservation
        // was released and re-reserved under the user's cart, not doubled.
        $this->assertSame(2, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));

        // withHeaders() merges into persistent default headers rather than
        // applying only to the next call — flush the Bearer token first, or
        // this "guest" request would silently still be authenticated and
        // read the user's cart instead of the (now-converted) guest one.
        $this->flushHeaders();
        $guestCartStillEmpty = $this->withHeaders($guestHeaders)->getJson('/api/v1/cart');
        $guestCartStillEmpty->assertJsonPath('data.item_count', 0);
    }

    public function test_merging_combines_quantities_when_the_user_already_has_the_same_item(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $guestHeaders = ['X-Guest-Session' => 'merge-test-session-2'];
        $this->withHeaders($guestHeaders)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertStatus(201);

        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina2@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        $headers = $this->authHeaders($user);
        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3])
            ->assertStatus(201);

        $response = $this->withHeaders($headers)->postJson('/api/v1/cart/merge', ['guest_session_token' => 'merge-test-session-2']);

        $response->assertStatus(200)->assertJsonPath('data.item_count', 5);
    }

    public function test_an_item_that_no_longer_has_enough_stock_is_dropped_rather_than_failing_the_whole_merge(): void
    {
        $variant = $this->makeVariant(stock: 3);
        $guestHeaders = ['X-Guest-Session' => 'merge-test-session-3'];
        $this->withHeaders($guestHeaders)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3])
            ->assertStatus(201);

        // Stock corrected downward after the guest reserved it (reserved_
        // quantity is left untouched, exactly like CheckoutTest's own
        // stale-reservation race test) — available_quantity goes negative
        // until the guest's own reservation is released during the merge.
        Inventory::where('variant_id', $variant->id)->update(['stock_quantity' => 1]);

        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina3@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);
        $headers = $this->authHeaders($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/cart/merge', ['guest_session_token' => 'merge-test-session-3']);

        $response->assertStatus(200)->assertJsonPath('data.item_count', 0);
    }

    public function test_merge_with_no_matching_guest_cart_is_a_harmless_no_op(): void
    {
        $user = User::create([
            'full_name' => 'Amina', 'email' => 'amina4@example.com',
            'password_hash' => Hash::make('password123'), 'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson('/api/v1/cart/merge', ['guest_session_token' => 'never-existed']);

        $response->assertStatus(200)->assertJsonPath('data.item_count', 0);
    }
}

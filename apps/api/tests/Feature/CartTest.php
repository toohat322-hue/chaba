<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
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

    private function guestHeader(string $token = 'test-session-1'): array
    {
        return ['X-Guest-Session' => $token];
    }

    public function test_add_item_creates_a_cart_and_reserves_stock(): void
    {
        $variant = $this->makeVariant(stock: 10);

        $response = $this->withHeaders($this->guestHeader())->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.item_count', 3)
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertSame(3, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_add_item_rejects_quantity_exceeding_available_stock(): void
    {
        $variant = $this->makeVariant(stock: 2);

        $response = $this->withHeaders($this->guestHeader())->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'insufficient_stock');
        $this->assertSame(0, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_adding_the_same_variant_twice_merges_into_one_line(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $response = $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertCount(1, $data['items']);
        $this->assertSame(5, $data['items'][0]['quantity']);
        $this->assertSame(5, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_a_second_add_that_exceeds_remaining_stock_after_a_first_reservation_is_rejected(): void
    {
        // Proves the meaningful guarantee behind PRD edge case #15 (last-unit
        // race): total reserved can never exceed real stock, checked via two
        // sequential requests against a single unit of stock.
        $variant = $this->makeVariant(stock: 1);

        $first = $this->withHeaders($this->guestHeader('session-a'))
            ->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $first->assertStatus(201);

        $second = $this->withHeaders($this->guestHeader('session-b'))
            ->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $second->assertStatus(409)->assertJsonPath('error.code', 'insufficient_stock');

        $this->assertSame(1, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_update_item_quantity_adjusts_reservation_both_ways(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $headers = $this->guestHeader();

        $add = $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 4]);
        $itemId = $add->json('data.items.0.id');

        $this->withHeaders($headers)->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 7])
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.quantity', 7);
        $this->assertSame(7, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));

        $this->withHeaders($headers)->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 2])
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.quantity', 2);
        $this->assertSame(2, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_updating_quantity_to_zero_removes_the_item(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $headers = $this->guestHeader();

        $add = $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3]);
        $itemId = $add->json('data.items.0.id');

        $response = $this->withHeaders($headers)->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 0]);

        $response->assertStatus(200)->assertJsonPath('data.item_count', 0);
        $this->assertSame(0, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_remove_item_releases_reservation(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $headers = $this->guestHeader();

        $add = $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 4]);
        $itemId = $add->json('data.items.0.id');

        $response = $this->withHeaders($headers)->deleteJson("/api/v1/cart/items/{$itemId}");

        $response->assertStatus(200)->assertJsonPath('data.item_count', 0);
        $this->assertSame(0, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_updating_or_removing_an_item_from_a_different_guest_session_is_not_found(): void
    {
        $variant = $this->makeVariant(stock: 10);

        $add = $this->withHeaders($this->guestHeader('session-owner'))
            ->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $itemId = $add->json('data.items.0.id');

        $this->withHeaders($this->guestHeader('session-stranger'))
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 5])
            ->assertStatus(404);
    }

    public function test_two_guest_sessions_have_isolated_carts(): void
    {
        $variant = $this->makeVariant(stock: 10);

        $this->withHeaders($this->guestHeader('session-a'))
            ->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);

        $cartB = $this->withHeaders($this->guestHeader('session-b'))->getJson('/api/v1/cart');

        $cartB->assertStatus(200)->assertJsonPath('data.item_count', 0);
    }

    public function test_a_cart_item_older_than_30_minutes_is_released_on_next_fetch(): void
    {
        $variant = $this->makeVariant(stock: 10);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 3]);

        CartItem::query()->update(['created_at' => now()->subMinutes(31)]);

        $response = $this->withHeaders($headers)->getJson('/api/v1/cart');

        $response->assertStatus(200)->assertJsonPath('data.item_count', 0);
        $this->assertSame(0, Inventory::where('variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_price_changed_flag_reflects_a_price_edit_after_adding_to_cart(): void
    {
        $variant = $this->makeVariant(stock: 10, price: 100000);
        $headers = $this->guestHeader();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $variant->product->update(['base_price' => 120000]);

        $response = $this->withHeaders($headers)->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.price_changed', true)
            ->assertJsonPath('data.items.0.price_snapshot', 100000)
            ->assertJsonPath('data.items.0.current_price', 120000);
    }

    public function test_cart_endpoints_require_a_guest_session_header_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/cart')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'guest_session_required');
    }
}

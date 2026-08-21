<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
    }

    public function test_create_update_and_archive_a_product_end_to_end(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'عطر جديد', 'name_fr' => 'Nouveau Parfum', 'name_en' => 'New Perfume',
            'base_price' => 500000,
            'status' => 'active',
            'sku' => 'TEST-SKU-001',
            'initial_stock' => 20,
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.name.en', 'New Perfume')
            ->assertJsonPath('data.variants.0.stock_quantity', 20)
            ->assertJsonPath('data.variants.0.low_stock_threshold', 5)
            ->assertJsonPath('data.status', 'active');

        $productId = $create->json('data.id');

        // The new product is immediately visible on the public storefront.
        $publicSlug = $create->json('data.slug');
        $this->getJson("/api/v1/products/{$publicSlug}")->assertStatus(200);

        $update = $this->withHeaders($headers)->patchJson("/api/v1/admin/products/{$productId}", [
            'base_price' => 550000,
            'featured' => true,
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('data.base_price', 550000)
            ->assertJsonPath('data.featured', true);

        $archive = $this->withHeaders($headers)->deleteJson("/api/v1/admin/products/{$productId}");
        $archive->assertStatus(200);

        // Archived products disappear from the public storefront...
        $this->getJson("/api/v1/products/{$publicSlug}")->assertStatus(404);

        // ...but the row itself still exists (not hard-deleted).
        $this->assertDatabaseHas('products', ['id' => $productId, 'status' => 'archived']);
    }

    public function test_renaming_a_product_slug_records_a_redirect_to_the_current_slug(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'عطر', 'name_fr' => 'Parfum', 'name_en' => 'Perfume',
            'slug' => 'old-perfume-slug',
            'base_price' => 500000, 'status' => 'active',
            'sku' => 'REDIRECT-SKU-001', 'initial_stock' => 5,
        ]);
        $productId = $create->json('data.id');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/products/{$productId}", [
            'slug' => 'new-perfume-slug',
        ])->assertStatus(200);

        $this->getJson('/api/v1/redirects/resolve?type=product&slug=old-perfume-slug')
            ->assertStatus(200)->assertJsonPath('data.slug', 'new-perfume-slug');

        // A slug that was never renamed away from has no redirect.
        $this->getJson('/api/v1/redirects/resolve?type=product&slug=never-existed')
            ->assertStatus(404);
    }

    public function test_creating_a_product_requires_a_unique_sku(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $payload = [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'DUPLICATE-SKU', 'initial_stock' => 1,
        ];

        $this->withHeaders($headers)->postJson('/api/v1/admin/products', $payload)->assertStatus(201);
        $this->withHeaders($headers)->postJson('/api/v1/admin/products', $payload)->assertStatus(400);
    }

    public function test_a_product_cannot_be_created_or_updated_with_a_zero_base_price(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 0, 'status' => 'active',
            'sku' => 'ZERO-PRICE-SKU', 'initial_stock' => 1,
        ]);
        $create->assertStatus(400)->assertJsonPath('error.field_errors.base_price.0', 'The base price field must be at least 1.');

        $existing = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'REAL-PRICE-SKU', 'initial_stock' => 1,
        ])->assertStatus(201);

        $this->withHeaders($headers)
            ->patchJson('/api/v1/admin/products/'.$existing->json('data.id'), ['base_price' => 0])
            ->assertStatus(400);
    }

    private function makeOrderForVariant(string $variantId): void
    {
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
            'delivery_method' => 'home', 'payment_method' => 'cod',
            'payment_status' => 'pending', 'order_status' => 'pending',
            'subtotal' => 100000, 'discount_total' => 0, 'delivery_fee' => 0, 'tax_total' => 0, 'grand_total' => 100000,
        ]);
        $order->items()->create([
            'variant_id' => $variantId, 'product_name_snapshot' => 'Product', 'sku_snapshot' => 'SKU',
            'unit_price' => 100000, 'quantity' => 1, 'line_total' => 100000,
        ]);
    }

    public function test_a_second_variant_can_be_added_with_its_own_price_and_stock(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-30ML', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');

        $addVariant = $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-100ML', 'size' => '100ml', 'price_override' => 250000,
            'low_stock_threshold' => 3, 'initial_stock' => 15,
        ]);

        $addVariant->assertStatus(201);
        $variants = $addVariant->json('data.variants');
        $this->assertCount(2, $variants);
        $this->assertSame('SKU-100ML', $variants[1]['sku']);
        $this->assertSame(250000, $variants[1]['price_override']);
        $this->assertSame(15, $variants[1]['stock_quantity']);
        $this->assertSame(3, $variants[1]['low_stock_threshold']);
    }

    public function test_a_variant_can_be_updated_including_its_low_stock_threshold(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-UPD', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');
        $variantId = $create->json('data.variants.0.id');

        $update = $this->withHeaders($headers)->patchJson("/api/v1/admin/products/{$productId}/variants/{$variantId}", [
            'color' => 'Amber', 'low_stock_threshold' => 2,
        ]);

        $update->assertStatus(200)
            ->assertJsonPath('data.variants.0.color', 'Amber')
            ->assertJsonPath('data.variants.0.low_stock_threshold', 2);
    }

    public function test_the_last_remaining_variant_of_a_product_cannot_be_deleted(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-ONLY', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');
        $variantId = $create->json('data.variants.0.id');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/admin/products/{$productId}/variants/{$variantId}")
            ->assertStatus(422);
    }

    public function test_a_variant_referenced_by_an_order_cannot_be_deleted(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-ORDERED', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');
        $orderedVariantId = $create->json('data.variants.0.id');

        $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-ORDERED-2', 'initial_stock' => 5,
        ]);

        $this->makeOrderForVariant($orderedVariantId);

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/admin/products/{$productId}/variants/{$orderedVariantId}")
            ->assertStatus(409);
    }

    public function test_a_non_last_unreferenced_variant_can_be_deleted(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-KEEP', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');

        $addVariant = $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-REMOVE', 'initial_stock' => 5,
        ]);
        $removableVariantId = $addVariant->json('data.variants.1.id');

        $delete = $this->withHeaders($headers)
            ->deleteJson("/api/v1/admin/products/{$productId}/variants/{$removableVariantId}");

        $delete->assertStatus(200);
        $this->assertCount(1, $delete->json('data.variants'));
    }

    public function test_a_variant_can_be_created_with_a_structured_size_compare_price_and_sort_order(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-10ML', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');

        $addVariant = $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-50ML', 'size_value' => 50, 'size_unit' => 'ml',
            'price_override' => 180000, 'compare_at_price' => 220000,
            'sort_order' => 2, 'is_active' => false, 'initial_stock' => 12,
        ]);

        $addVariant->assertStatus(201);
        $variant = collect($addVariant->json('data.variants'))->firstWhere('sku', 'SKU-50ML');
        $this->assertEquals(50, $variant['size_value']);
        $this->assertSame('ml', $variant['size_unit']);
        $this->assertSame(220000, $variant['compare_at_price']);
        $this->assertSame(2, $variant['sort_order']);
        $this->assertFalse($variant['is_active']);
    }

    public function test_two_variants_of_the_same_product_cannot_share_a_size(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-DUPBASE', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');

        $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-DUP-A', 'size_value' => 10, 'size_unit' => 'ml', 'initial_stock' => 5,
        ])->assertStatus(201);

        $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-DUP-B', 'size_value' => 10, 'size_unit' => 'ml', 'initial_stock' => 5,
        ])->assertStatus(409)->assertJsonPath('error.code', 'duplicate_variant_size');
    }

    public function test_an_inactive_variant_is_hidden_from_the_public_product_but_visible_to_admin(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'SKU-ACTIVE', 'initial_stock' => 10,
        ]);
        $productId = $create->json('data.id');
        $slug = $create->json('data.slug');

        $this->withHeaders($headers)->postJson("/api/v1/admin/products/{$productId}/variants", [
            'sku' => 'SKU-DISABLED', 'size_value' => 100, 'size_unit' => 'ml',
            'is_active' => false, 'initial_stock' => 5,
        ])->assertStatus(201);

        $public = $this->getJson("/api/v1/products/{$slug}");
        $publicSkus = collect($public->json('data.variants'))->pluck('sku');
        $this->assertTrue($publicSkus->contains('SKU-ACTIVE'));
        $this->assertFalse($publicSkus->contains('SKU-DISABLED'));

        $admin = $this->withHeaders($headers)->getJson("/api/v1/admin/products/{$productId}");
        $adminSkus = collect($admin->json('data.variants'))->pluck('sku');
        $this->assertTrue($adminSkus->contains('SKU-DISABLED'));
    }
}

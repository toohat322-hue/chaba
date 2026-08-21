<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(Category $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 150000,
            'status' => 'active',
            'avg_rating' => 4.5,
            'review_count' => 10,
        ], $overrides));
    }

    public function test_product_show_includes_related_products_from_the_same_category(): void
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = $this->makeProduct($category);
        $sibling = $this->makeProduct($category);
        $otherCategory = Category::create([
            'name_ar' => 'فئة أخرى', 'name_fr' => 'Autre', 'name_en' => 'Other',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $unrelated = $this->makeProduct($otherCategory);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200);
        $relatedIds = collect($response->json('data.related'))->pluck('id');
        $this->assertTrue($relatedIds->contains($sibling->id));
        $this->assertFalse($relatedIds->contains($unrelated->id));
        $this->assertFalse($relatedIds->contains($product->id));
    }

    public function test_the_cached_product_listing_reflects_a_product_update(): void
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = $this->makeProduct($category, ['name_en' => 'Original Name']);

        $this->getJson('/api/v1/products')
            ->assertJsonPath('data.items.0.name.en', 'Original Name');

        $product->update(['name_en' => 'Renamed Product']);

        $this->getJson('/api/v1/products')
            ->assertJsonPath('data.items.0.name.en', 'Renamed Product');
    }

    public function test_an_inactive_variants_stock_and_price_do_not_affect_the_product_listing(): void
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = $this->makeProduct($category, ['base_price' => 350000]);

        // Active variant: no stock, price between the inactive variant's
        // cheap override and the product's base_price.
        $activeVariant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SKU-ACTIVE-'.Str::random(6),
            'price_override' => 300000, 'is_active' => true,
        ]);
        Inventory::create(['variant_id' => $activeVariant->id, 'stock_quantity' => 0, 'reserved_quantity' => 0]);

        // Inactive variant: plenty of stock, cheap price — must not leak
        // into in_stock/startingPrice on the public listing.
        $inactiveVariant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'SKU-INACTIVE-'.Str::random(6),
            'price_override' => 50000, 'is_active' => false,
        ]);
        Inventory::create(['variant_id' => $inactiveVariant->id, 'stock_quantity' => 100, 'reserved_quantity' => 0]);

        $response = $this->getJson('/api/v1/products');

        $response->assertJsonPath('data.items.0.in_stock', false);
        $response->assertJsonPath('data.items.0.price', 300000);
    }

    public function test_product_seo_fields_and_updated_at_are_exposed_publicly(): void
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = $this->makeProduct($category, [
            'seo_title' => 'عطر مافرو | شابة',
            'seo_description' => 'اكتشف عطر مافرو الأصلي من شابة.',
        ]);

        $show = $this->getJson("/api/v1/products/{$product->slug}");
        $show->assertJsonPath('data.seo_title', 'عطر مافرو | شابة')
            ->assertJsonPath('data.seo_description', 'اكتشف عطر مافرو الأصلي من شابة.');
        $this->assertNotNull($show->json('data.updated_at'));

        $list = $this->getJson('/api/v1/products');
        $this->assertNotNull($list->json('data.items.0.updated_at'));
    }

    public function test_the_cached_category_tree_reflects_a_category_update(): void
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Original Category',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        $this->getJson('/api/v1/categories')
            ->assertJsonPath('data.0.name.en', 'Original Category');

        $category->update(['name_en' => 'Renamed Category']);

        $this->getJson('/api/v1/categories')
            ->assertJsonPath('data.0.name.en', 'Renamed Category');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
    }

    private function makeProduct(Category $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name_ar' => 'عطر الورد', 'name_fr' => 'Parfum Rose', 'name_en' => 'Rose Perfume',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 150000,
            'status' => 'active',
            'avg_rating' => 4.0,
            'review_count' => 5,
        ], $overrides));
    }

    public function test_search_results_can_be_sorted_by_price_overriding_relevance(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name_en' => 'Rose Perfume Expensive', 'base_price' => 900000]);
        $this->makeProduct($category, ['name_en' => 'Rose Perfume Cheap', 'base_price' => 100000]);

        $response = $this->getJson('/api/v1/search?q=Rose&sort=price_asc');

        $response->assertStatus(200);
        $names = collect($response->json('data.items'))->pluck('name.en');
        $this->assertSame('Rose Perfume Cheap', $names->first());
        $this->assertSame('Rose Perfume Expensive', $names->last());
    }

    public function test_search_defaults_to_relevance_ordering_without_an_explicit_sort(): void
    {
        $category = $this->makeCategory();
        $product = $this->makeProduct($category, ['name_en' => 'Rose Perfume']);

        $response = $this->getJson('/api/v1/search?q=Rose');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.id', $product->id);
    }

    public function test_search_results_can_be_filtered_by_price_range(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name_en' => 'Rose Perfume A', 'base_price' => 100000]);
        $this->makeProduct($category, ['name_en' => 'Rose Perfume B', 'base_price' => 900000]);

        $response = $this->getJson('/api/v1/search?q=Rose&price_max=200000');

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('Rose Perfume A', $items[0]['name']['en']);
    }

    public function test_search_results_are_paginated(): void
    {
        $category = $this->makeCategory();
        for ($i = 0; $i < 30; $i++) {
            $this->makeProduct($category, ['name_en' => "Rose Perfume {$i}"]);
        }

        $page1 = $this->getJson('/api/v1/search?q=Rose');
        $page1->assertStatus(200)
            ->assertJsonPath('data.meta.total', 30)
            ->assertJsonPath('data.meta.last_page', 2);
        $this->assertCount(24, $page1->json('data.items'));

        $page2 = $this->getJson('/api/v1/search?q=Rose&page=2');
        $page2->assertJsonPath('data.meta.current_page', 2);
        $this->assertCount(6, $page2->json('data.items'));
    }

    public function test_an_empty_query_still_returns_the_popular_products_fallback(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['featured' => true]);

        $response = $this->getJson('/api/v1/search?q=');

        $response->assertStatus(200)->assertJsonPath('data.fallback', true);
    }

    public function test_a_query_with_zero_matches_returns_no_results_with_suggestions(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['featured' => true, 'name_en' => 'Oud Malaki']);

        $response = $this->getJson('/api/v1/search?q=zzzzznomatch');

        $response->assertStatus(200)
            ->assertJsonPath('data.no_results', true);
        $this->assertNotEmpty($response->json('data.suggestions'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeroSlideControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name_ar' => 'عطر الورد', 'name_fr' => 'Parfum de Rose', 'name_en' => 'Rose Perfume',
            'description_ar' => 'وصف المنتج', 'description_fr' => 'Description', 'description_en' => 'Description',
            'slug' => 'product-'.Str::random(8), 'base_price' => 150000, 'status' => 'active',
        ], $overrides));
    }

    public function test_only_active_slides_within_the_date_window_are_returned_in_sort_order(): void
    {
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();
        $productC = $this->makeProduct();

        HeroSlide::create(['product_id' => $productA->id, 'is_active' => true, 'sort_order' => 1]);
        HeroSlide::create(['product_id' => $productB->id, 'is_active' => false, 'sort_order' => 0]);
        HeroSlide::create(['product_id' => $productC->id, 'is_active' => true, 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/hero-slides');

        $response->assertStatus(200);
        $slugs = collect($response->json('data'))->pluck('product.slug');
        $this->assertSame([$productC->slug, $productA->slug], $slugs->all());
    }

    public function test_a_slide_outside_its_date_window_is_excluded(): void
    {
        $future = $this->makeProduct();
        $expired = $this->makeProduct();
        $current = $this->makeProduct();

        HeroSlide::create(['product_id' => $future->id, 'is_active' => true, 'start_date' => now()->addDay()]);
        HeroSlide::create(['product_id' => $expired->id, 'is_active' => true, 'end_date' => now()->subDay()]);
        HeroSlide::create(['product_id' => $current->id, 'is_active' => true, 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);

        $response = $this->getJson('/api/v1/hero-slides');

        $slugs = collect($response->json('data'))->pluck('product.slug');
        $this->assertSame([$current->slug], $slugs->all());
    }

    public function test_a_slide_whose_product_is_not_active_is_excluded(): void
    {
        $draftProduct = $this->makeProduct(['status' => 'draft']);
        HeroSlide::create(['product_id' => $draftProduct->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/hero-slides');

        $this->assertCount(0, $response->json('data'));
    }

    public function test_empty_title_and_subtitle_fall_back_to_the_product_data(): void
    {
        $product = $this->makeProduct();
        HeroSlide::create(['product_id' => $product->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/hero-slides');

        $response->assertJsonPath('data.0.title.ar', 'عطر الورد');
        $response->assertJsonPath('data.0.subtitle.ar', 'وصف المنتج');
        $response->assertJsonPath('data.0.cta_label.en', 'Shop Now');
    }

    public function test_a_custom_title_overrides_the_product_name(): void
    {
        $product = $this->makeProduct();
        HeroSlide::create([
            'product_id' => $product->id,
            'is_active' => true,
            'title_ar' => 'عنوان مخصص',
            'cta_label_en' => 'Discover',
        ]);

        $response = $this->getJson('/api/v1/hero-slides');

        $response->assertJsonPath('data.0.title.ar', 'عنوان مخصص');
        $response->assertJsonPath('data.0.cta_label.en', 'Discover');
    }
}

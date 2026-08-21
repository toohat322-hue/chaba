<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminProductImageTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
        Storage::fake('s3');
    }

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

    public function test_alt_text_can_be_edited_after_upload(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $product = $this->makeProduct();

        $upload = $this->withHeaders($headers)->post("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('bottle.jpg'),
        ]);
        $upload->assertStatus(201);
        $imageId = $upload->json('data.id');

        $update = $this->withHeaders($headers)->patchJson(
            "/api/v1/admin/products/{$product->id}/images/{$imageId}",
            ['alt_text' => 'Amber perfume bottle, front view'],
        );

        $update->assertStatus(200)->assertJsonPath('data.alt_text', 'Amber perfume bottle, front view');
    }

    public function test_images_can_be_reordered(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $product = $this->makeProduct();

        $first = $this->withHeaders($headers)->post("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('one.jpg'),
        ])->json('data.id');
        $second = $this->withHeaders($headers)->post("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('two.jpg'),
        ])->json('data.id');

        $reorder = $this->withHeaders($headers)->patchJson(
            "/api/v1/admin/products/{$product->id}/images/reorder",
            ['image_ids' => [$second, $first]],
        );

        $reorder->assertStatus(200);
        $items = $reorder->json('data.items');
        $this->assertSame($second, $items[0]['id']);
        $this->assertSame(0, $items[0]['sort_order']);
        $this->assertSame($first, $items[1]['id']);
        $this->assertSame(1, $items[1]['sort_order']);
    }

    public function test_reorder_rejects_an_image_id_from_a_different_product(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();

        $imageA = $this->withHeaders($headers)->post("/api/v1/admin/products/{$productA->id}/images", [
            'image' => UploadedFile::fake()->image('a.jpg'),
        ])->json('data.id');
        $imageB = $this->withHeaders($headers)->post("/api/v1/admin/products/{$productB->id}/images", [
            'image' => UploadedFile::fake()->image('b.jpg'),
        ])->json('data.id');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/products/{$productA->id}/images/reorder", ['image_ids' => [$imageA, $imageB]])
            ->assertStatus(422);
    }
}

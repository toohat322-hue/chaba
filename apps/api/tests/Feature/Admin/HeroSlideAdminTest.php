<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class HeroSlideAdminTest extends TestCase
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

    public function test_marketing_manager_can_create_update_and_delete_a_slide(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $product = $this->makeProduct();

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/hero-slides', [
            'product_id' => $product->id,
            'title_ar' => 'عنوان',
        ]);
        $create->assertStatus(201)->assertJsonPath('data.title.ar', 'عنوان')->assertJsonPath('data.product.slug', $product->slug);
        $id = $create->json('data.id');

        $update = $this->withHeaders($headers)->patchJson("/api/v1/admin/hero-slides/{$id}", ['is_active' => false]);
        $update->assertStatus(200)->assertJsonPath('data.is_active', false);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/hero-slides/{$id}")->assertStatus(200);
        $this->assertNull(HeroSlide::find($id));
    }

    public function test_a_role_without_hero_slides_edit_cannot_create_a_slide(): void
    {
        [, $headers] = $this->actingAsRole('Customer Support');
        $product = $this->makeProduct();

        $this->withHeaders($headers)->postJson('/api/v1/admin/hero-slides', ['product_id' => $product->id])
            ->assertStatus(403);
    }

    public function test_creating_a_slide_with_an_unknown_product_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');

        $this->withHeaders($headers)->postJson('/api/v1/admin/hero-slides', ['product_id' => (string) Str::uuid()])
            ->assertStatus(400);
    }

    public function test_desktop_and_mobile_images_can_be_uploaded_and_replaced(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $product = $this->makeProduct();
        $slide = HeroSlide::create(['product_id' => $product->id]);

        $upload = $this->withHeaders($headers)->post("/api/v1/admin/hero-slides/{$slide->id}/image", [
            'image' => UploadedFile::fake()->image('desktop.jpg'),
        ]);
        $upload->assertStatus(200);
        $this->assertNotNull($upload->json('data.image_url'));

        $mobileUpload = $this->withHeaders($headers)->post("/api/v1/admin/hero-slides/{$slide->id}/mobile-image", [
            'image' => UploadedFile::fake()->image('mobile.jpg'),
        ]);
        $mobileUpload->assertStatus(200);
        $this->assertNotNull($mobileUpload->json('data.mobile_image_url'));

        // Re-uploading replaces rather than duplicating.
        $replace = $this->withHeaders($headers)->post("/api/v1/admin/hero-slides/{$slide->id}/image", [
            'image' => UploadedFile::fake()->image('new-desktop.jpg'),
        ]);
        $replace->assertStatus(200);
        $this->assertNotSame($upload->json('data.image_url'), $replace->json('data.image_url'));
    }

    public function test_uploading_a_non_image_file_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $product = $this->makeProduct();
        $slide = HeroSlide::create(['product_id' => $product->id]);

        $this->withHeaders($headers)->post("/api/v1/admin/hero-slides/{$slide->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(400);
    }

    public function test_slides_can_be_reordered_by_id_list(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $first = HeroSlide::create(['product_id' => $this->makeProduct()->id, 'sort_order' => 0]);
        $second = HeroSlide::create(['product_id' => $this->makeProduct()->id, 'sort_order' => 1]);

        $reorder = $this->withHeaders($headers)->patchJson('/api/v1/admin/hero-slides/reorder', [
            'slide_ids' => [$second->id, $first->id],
        ]);

        $reorder->assertStatus(200);
        $this->assertSame(0, HeroSlide::find($second->id)->sort_order);
        $this->assertSame(1, HeroSlide::find($first->id)->sort_order);
    }

    public function test_reorder_rejects_a_list_that_does_not_match_the_full_current_set(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        HeroSlide::create(['product_id' => $this->makeProduct()->id]);
        HeroSlide::create(['product_id' => $this->makeProduct()->id]);

        $this->withHeaders($headers)
            ->patchJson('/api/v1/admin/hero-slides/reorder', ['slide_ids' => [(string) Str::uuid()]])
            ->assertStatus(422);
    }

    public function test_deleting_the_linked_product_cascades_and_removes_the_slide(): void
    {
        $product = $this->makeProduct();
        $slide = HeroSlide::create(['product_id' => $product->id]);

        $product->delete();

        $this->assertNull(HeroSlide::find($slide->id));
    }
}

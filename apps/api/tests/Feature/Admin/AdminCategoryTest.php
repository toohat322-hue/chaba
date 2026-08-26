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

class AdminCategoryTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
        Storage::fake('s3');
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
    }

    public function test_create_and_update_a_category(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/categories', [
            'name_ar' => 'فئة جديدة', 'name_fr' => 'Nouvelle', 'name_en' => 'New Category',
        ]);

        $create->assertStatus(201)->assertJsonPath('data.name.en', 'New Category');
        $categoryId = $create->json('data.id');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/categories/{$categoryId}", [
            'is_active' => false,
        ])->assertStatus(200)->assertJsonPath('data.is_active', false);
    }

    public function test_category_seo_fields_can_be_set_and_updated(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $create = $this->withHeaders($headers)->postJson('/api/v1/admin/categories', [
            'name_ar' => 'فئة جديدة', 'name_fr' => 'Nouvelle', 'name_en' => 'New Category',
            'seo_title' => 'عطور نسائية | شابة',
            'seo_description' => 'تسوق عطور نسائية أصلية في الجزائر.',
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.seo_title', 'عطور نسائية | شابة')
            ->assertJsonPath('data.seo_description', 'تسوق عطور نسائية أصلية في الجزائر.');
        $categoryId = $create->json('data.id');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/categories/{$categoryId}", [
            'seo_title' => 'عطور نسائية فاخرة | شابة',
        ])->assertStatus(200)->assertJsonPath('data.seo_title', 'عطور نسائية فاخرة | شابة');

        $public = $this->getJson("/api/v1/categories/{$create->json('data.slug')}");
        $public->assertJsonPath('data.seo_title', 'عطور نسائية فاخرة | شابة');
    }

    public function test_renaming_a_category_slug_records_a_redirect_to_the_current_slug(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'old-category-slug', 'is_active' => true,
        ]);

        $this->withHeaders($headers)->patchJson("/api/v1/admin/categories/{$category->id}", [
            'slug' => 'new-category-slug',
        ])->assertStatus(200);

        $resolve = $this->getJson('/api/v1/redirects/resolve?type=category&slug=old-category-slug');
        $resolve->assertStatus(200)->assertJsonPath('data.slug', 'new-category-slug');
    }

    public function test_deleting_a_category_with_products_is_blocked(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name_ar' => 'م', 'name_fr' => 'p', 'name_en' => 'p',
            'slug' => 'p-'.Str::random(8), 'base_price' => 1000, 'status' => 'active',
        ]);

        $response = $this->withHeaders($headers)->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(409)->assertJsonPath('error.code', 'category_has_products');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_an_empty_category_succeeds(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_an_image_can_be_uploaded_and_replaced(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $upload = $this->withHeaders($headers)->post("/api/v1/admin/categories/{$category->id}/image", [
            'image' => UploadedFile::fake()->image('cat.jpg'),
        ]);
        $upload->assertStatus(200);
        $this->assertNotNull($upload->json('data.image_url'));

        // Re-uploading replaces rather than duplicating.
        $replace = $this->withHeaders($headers)->post("/api/v1/admin/categories/{$category->id}/image", [
            'image' => UploadedFile::fake()->image('cat-2.jpg'),
        ]);
        $replace->assertStatus(200);
        $this->assertNotSame($upload->json('data.image_url'), $replace->json('data.image_url'));
    }

    public function test_uploading_a_non_image_file_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $this->withHeaders($headers)->post("/api/v1/admin/categories/{$category->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(400);
    }

    public function test_an_image_can_be_removed_without_deleting_the_category(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();

        $this->withHeaders($headers)->post("/api/v1/admin/categories/{$category->id}/image", [
            'image' => UploadedFile::fake()->image('cat.jpg'),
        ])->assertStatus(200);

        $remove = $this->withHeaders($headers)->deleteJson("/api/v1/admin/categories/{$category->id}/image");
        $remove->assertStatus(200)->assertJsonPath('data.image_url', null);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'image_url' => null]);
    }

    public function test_a_role_without_categories_edit_cannot_upload_an_image(): void
    {
        [, $headers] = $this->actingAsRole('Customer Support');
        $category = $this->makeCategory();

        $this->withHeaders($headers)->post("/api/v1/admin/categories/{$category->id}/image", [
            'image' => UploadedFile::fake()->image('cat.jpg'),
        ])->assertStatus(403);
    }
}

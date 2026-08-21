<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    /** @return array{0: Product, 1: Review} */
    private function makeReview(string $status = 'pending'): array
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
        $reviewer = User::create([
            'full_name' => 'Reviewer',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
            'title' => 'Okay',
            'is_verified_purchase' => false,
            'status' => $status,
        ]);

        return [$product, $review];
    }

    public function test_a_customer_without_a_role_is_forbidden_from_admin_reviews(): void
    {
        $this->seedRbac();
        $this->makeReview();
        [, $headers] = $this->actingAsCustomer();

        $this->withHeaders($headers)->getJson('/api/v1/admin/reviews')->assertStatus(403);
    }

    public function test_a_role_without_reviews_view_is_forbidden(): void
    {
        $this->seedRbac();
        $this->makeReview();
        [, $headers] = $this->actingAsRole('Order Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/reviews')->assertStatus(403);
    }

    public function test_product_manager_can_list_and_filter_reviews_by_status(): void
    {
        $this->seedRbac();
        $this->makeReview(status: 'pending');
        $this->makeReview(status: 'approved');
        [, $headers] = $this->actingAsRole('Product Manager');

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/reviews?status=pending');

        $response->assertStatus(200)->assertJsonCount(1, 'data.items');
        $this->assertSame('pending', $response->json('data.items.0.status'));
    }

    public function test_approving_a_pending_review_recalculates_the_product_average(): void
    {
        $this->seedRbac();
        [$product, $review] = $this->makeReview(status: 'pending');
        [, $headers] = $this->actingAsRole('Product Manager');

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/reviews/{$review->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'approved');
        $this->assertEquals(3.00, (float) $product->fresh()->avg_rating);
        $this->assertSame(1, $product->fresh()->review_count);
    }

    public function test_a_role_without_reviews_moderate_cannot_change_status(): void
    {
        $this->seedRbac();
        [, $review] = $this->makeReview(status: 'pending');
        [, $headers] = $this->actingAsRole('Customer Support');

        $this->withHeaders($headers)->patchJson("/api/v1/admin/reviews/{$review->id}/status", ['status' => 'approved'])
            ->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Commune;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wilaya;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: array<string, string>} */
    private function actingAsCustomer(): array
    {
        $user = User::create([
            'full_name' => 'Amina Test',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    public function test_a_customer_sees_only_their_own_notifications(): void
    {
        [$user, $headers] = $this->actingAsCustomer();
        [$otherUser] = $this->actingAsCustomer();

        Notification::create(['user_id' => $user->id, 'channel' => 'in_app', 'event_type' => 'order_created', 'status' => 'sent', 'sent_at' => now()]);
        Notification::create(['user_id' => $otherUser->id, 'channel' => 'in_app', 'event_type' => 'order_created', 'status' => 'sent', 'sent_at' => now()]);

        $response = $this->withHeaders($headers)->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.event_type', 'order_created')
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_marking_a_notification_read_updates_unread_count(): void
    {
        [$user, $headers] = $this->actingAsCustomer();
        $notification = Notification::create([
            'user_id' => $user->id, 'channel' => 'in_app', 'event_type' => 'order_shipped', 'status' => 'sent', 'sent_at' => now(),
        ]);

        $this->withHeaders($headers)->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(200)
            ->assertJsonPath('data.event_type', 'order_shipped');

        $this->assertNotNull($notification->fresh()->read_at);

        $this->withHeaders($headers)->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_mark_all_read_clears_every_unread_notification(): void
    {
        [$user, $headers] = $this->actingAsCustomer();
        Notification::create(['user_id' => $user->id, 'channel' => 'in_app', 'event_type' => 'order_created', 'status' => 'sent', 'sent_at' => now()]);
        Notification::create(['user_id' => $user->id, 'channel' => 'in_app', 'event_type' => 'order_delivered', 'status' => 'sent', 'sent_at' => now()]);

        $this->withHeaders($headers)->patchJson('/api/v1/notifications/read-all')->assertStatus(200);

        $this->withHeaders($headers)->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_a_notification_can_be_deleted(): void
    {
        [$user, $headers] = $this->actingAsCustomer();
        $notification = Notification::create([
            'user_id' => $user->id, 'channel' => 'in_app', 'event_type' => 'order_created', 'status' => 'sent', 'sent_at' => now(),
        ]);

        $this->withHeaders($headers)->deleteJson("/api/v1/notifications/{$notification->id}")->assertStatus(200);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_a_customer_cannot_read_or_delete_someone_elses_notification(): void
    {
        [, $headers] = $this->actingAsCustomer();
        [$otherUser] = $this->actingAsCustomer();
        $notification = Notification::create([
            'user_id' => $otherUser->id, 'channel' => 'in_app', 'event_type' => 'order_created', 'status' => 'sent', 'sent_at' => now(),
        ]);

        $this->withHeaders($headers)->patchJson("/api/v1/notifications/{$notification->id}/read")->assertStatus(404);
        $this->withHeaders($headers)->deleteJson("/api/v1/notifications/{$notification->id}")->assertStatus(404);
    }

    public function test_checkout_creates_an_order_created_notification_for_a_logged_in_customer(): void
    {
        [$user, $headers] = $this->actingAsCustomer();

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 100000, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'ا', 'name_fr' => 'A', 'name_en' => 'A']);

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->withHeaders($headers)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test', 'customer_phone' => '0555222333',
            'address' => [
                'full_name' => 'Amina Test', 'phone' => '0555222333',
                'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue 1',
            ],
            'delivery_method' => 'home', 'payment_method' => 'cod',
        ])->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id, 'event_type' => 'order_created', 'channel' => 'in_app',
        ]);
    }
}

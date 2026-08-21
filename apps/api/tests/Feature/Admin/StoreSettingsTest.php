<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Commune;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    public function test_show_returns_defaults_on_first_access(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');

        $this->withHeaders($headers)->getJson('/api/v1/admin/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.tax_rate_bps', 0)
            ->assertJsonPath('data.payment_providers.cod', true);
    }

    public function test_super_admin_can_update_settings(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');

        $response = $this->withHeaders($headers)->patchJson('/api/v1/admin/settings', [
            'store_name' => 'CHABA Perfumes',
            'support_email' => 'support@chaba.dz',
            'support_phone' => '0555000000',
            'store_address' => 'Alger',
            'tax_rate_bps' => 1900,
        ]);

        $response->assertStatus(200)->assertJsonPath('data.tax_rate_bps', 1900);
        $this->withHeaders($headers)->getJson('/api/v1/admin/settings')
            ->assertJsonPath('data.store_name', 'CHABA Perfumes');
    }

    public function test_a_role_without_settings_permission_is_forbidden(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Order Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/settings')->assertStatus(403);
        $this->withHeaders($headers)->patchJson('/api/v1/admin/settings', ['store_name' => 'x', 'tax_rate_bps' => 0])
            ->assertStatus(403);
    }

    public function test_configured_tax_rate_is_applied_at_checkout(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');
        $this->withHeaders($headers)->patchJson('/api/v1/admin/settings', [
            'store_name' => 'CHABA', 'tax_rate_bps' => 1000, // 10%
        ])->assertStatus(200);

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        $wilaya = Wilaya::create(['code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::create(['wilaya_code' => $wilaya->code, 'name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre']);

        $guestHeaders = ['X-Guest-Session' => 'tax-test-session'];
        $this->withHeaders($guestHeaders)->postJson('/api/v1/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertStatus(201);

        $response = $this->withHeaders($guestHeaders)->postJson('/api/v1/checkout', [
            'customer_name' => 'Amina Test',
            'customer_phone' => '0555222333',
            'delivery_method' => 'home',
            'payment_method' => 'cod',
            'address' => [
                'full_name' => 'Amina Test',
                'phone' => '0555222333',
                'wilaya_code' => $wilaya->code,
                'commune_id' => $commune->id,
                'address_line' => 'Rue Test',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order.tax_total', 10000)
            ->assertJsonPath('data.order.grand_total', 110000);
    }

    public function test_super_admin_can_toggle_whatsapp_orders_and_it_is_audit_logged(): void
    {
        $this->seedRbac();
        [$admin, $headers] = $this->actingAsRole('Super Admin');

        $response = $this->withHeaders($headers)->patchJson('/api/v1/admin/settings', [
            'store_name' => 'CHABA', 'tax_rate_bps' => 0,
            'whatsapp_number' => '213555111222', 'whatsapp_orders_enabled' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.whatsapp.orders_enabled', true)
            ->assertJsonPath('data.whatsapp.number', '213555111222');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }
}

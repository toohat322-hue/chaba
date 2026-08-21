<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_updating_a_products_price_writes_a_correctly_diffed_audit_log_entry(): void
    {
        [$actor, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عطر', 'name_fr' => 'Parfum', 'name_en' => 'Existing Perfume',
            'slug' => 'p-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/products/{$product->id}", ['base_price' => 150000])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'product.updated',
            'entity_type' => 'Product',
            'entity_id' => $product->id,
            'subject_label' => 'Existing Perfume',
        ]);

        $log = AuditLog::where('entity_id', $product->id)->firstOrFail();
        $this->assertSame(100000, $log->before['base_price']);
        $this->assertSame(150000, $log->after['base_price']);
        $this->assertArrayNotHasKey('name_en', $log->after);
    }

    public function test_archiving_a_product_writes_an_audit_log_entry(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $category = $this->makeCategory();
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عطر', 'name_fr' => 'Parfum', 'name_en' => 'Doomed Perfume',
            'slug' => 'p-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/products/{$product->id}")->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.archived',
            'subject_label' => 'Doomed Perfume',
        ]);
    }

    public function test_deactivating_a_coupon_writes_an_audit_log_entry(): void
    {
        [, $headers] = $this->actingAsRole('Marketing Manager');
        $coupon = Coupon::create([
            'code' => 'SAVE10', 'type' => 'percentage', 'value' => 10,
            'usage_limit_total' => 100, 'usage_limit_per_customer' => 1, 'is_active' => true,
        ]);

        $this->withHeaders($headers)->deleteJson("/api/v1/admin/coupons/{$coupon->id}")->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon.deactivated',
            'subject_label' => 'SAVE10',
        ]);
    }

    public function test_the_audit_log_is_only_visible_to_roles_with_the_audit_logs_permission(): void
    {
        [, $productManagerHeaders] = $this->actingAsRole('Product Manager');
        $this->withHeaders($productManagerHeaders)->getJson('/api/v1/admin/audit-logs')->assertStatus(403);

        [, $superAdminHeaders] = $this->actingAsRole('Super Admin');
        $this->withHeaders($superAdminHeaders)->getJson('/api/v1/admin/audit-logs')->assertStatus(200);
    }

    public function test_audit_log_entries_can_be_filtered_by_action_prefix(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $category = $this->makeCategory();

        $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'base_price' => 100000, 'status' => 'active',
            'sku' => 'FILTER-SKU', 'initial_stock' => 1,
        ])->assertStatus(201);

        $this->withHeaders($headers)->postJson('/api/v1/admin/coupons', [
            'code' => 'FILTERTEST', 'type' => 'percentage', 'value' => 5,
            'usage_limit_total' => 10, 'usage_limit_per_customer' => 1,
        ])->assertStatus(201);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/audit-logs?action=product');

        $response->assertStatus(200);
        $actions = collect($response->json('data.items'))->pluck('action');
        $this->assertNotEmpty($actions);
        $this->assertTrue($actions->every(fn ($action) => str_starts_with($action, 'product')));
    }

    /**
     * Regression coverage: save() calls syncOriginal() once it commits, so
     * a diff captured *after* save (rather than after fill(), before save())
     * would silently record the new value as both old and new — this test
     * would have caught that bug.
     */
    public function test_an_inventory_adjustment_logs_the_real_before_and_after_stock(): void
    {
        [, $headers] = $this->actingAsRole('Inventory Manager');
        $category = $this->makeCategory();
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'a', 'name_fr' => 'a', 'name_en' => 'a',
            'slug' => 'p-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'STOCK-SKU-'.Str::random(6)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/admin/inventory/{$variant->id}/adjust", ['delta' => 5, 'reason' => 'restock'])
            ->assertStatus(200);

        $log = AuditLog::where('entity_id', $variant->id)->where('action', 'inventory.adjusted')->firstOrFail();
        $this->assertSame(10, $log->before['stock_quantity']);
        $this->assertSame(15, $log->after['stock_quantity']);
    }
}

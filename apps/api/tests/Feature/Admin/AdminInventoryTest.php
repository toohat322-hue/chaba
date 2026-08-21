<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    private function makeVariant(int $stock = 10, int $reserved = 0): ProductVariant
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'م', 'name_fr' => 'p', 'name_en' => 'p',
            'slug' => 'p-'.Str::random(8), 'base_price' => 100000, 'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create([
            'variant_id' => $variant->id, 'stock_quantity' => $stock,
            'reserved_quantity' => $reserved, 'low_stock_threshold' => 5,
        ]);

        return $variant;
    }

    public function test_positive_adjustment_increases_stock_and_writes_an_audit_row(): void
    {
        [$actor, $headers] = $this->actingAsRole('Inventory Manager');
        $variant = $this->makeVariant(stock: 10);

        $response = $this->withHeaders($headers)->postJson("/api/v1/admin/inventory/{$variant->id}/adjust", [
            'delta' => 15,
            'reason' => 'restock',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.stock_quantity', 25);

        $this->assertDatabaseHas('inventory_adjustments', [
            'variant_id' => $variant->id,
            'delta' => 15,
            'reason' => 'restock',
            'actor_id' => $actor->id,
        ]);
    }

    public function test_negative_adjustment_that_would_go_below_reserved_is_rejected(): void
    {
        [, $headers] = $this->actingAsRole('Inventory Manager');
        $variant = $this->makeVariant(stock: 10, reserved: 4);

        $response = $this->withHeaders($headers)->postJson("/api/v1/admin/inventory/{$variant->id}/adjust", [
            'delta' => -8,
            'reason' => 'damage',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'stock_below_reserved');
        $this->assertSame(10, Inventory::where('variant_id', $variant->id)->value('stock_quantity'));
    }

    public function test_product_manager_cannot_adjust_inventory(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');
        $variant = $this->makeVariant();

        $this->withHeaders($headers)->postJson("/api/v1/admin/inventory/{$variant->id}/adjust", [
            'delta' => 5, 'reason' => 'restock',
        ])->assertStatus(403);
    }
}

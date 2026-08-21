<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    private function makeOrderWithItem(string $status, int $grandTotal, string $productName, int $quantity): Order
    {
        $order = $this->makeOrder($status, $grandTotal);

        OrderItem::create([
            'order_id' => $order->id,
            'variant_id' => ProductVariant::first()->id,
            'product_name_snapshot' => $productName,
            'sku_snapshot' => 'SKU-'.Str::random(8),
            'unit_price' => $grandTotal,
            'quantity' => $quantity,
            'line_total' => $grandTotal * $quantity,
        ]);

        return $order;
    }

    private function makeOrder(string $status, int $grandTotal): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Product',
            'slug' => 'product-'.Str::random(8),
            'base_price' => $grandTotal,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-'.Str::random(8)]);
        Inventory::create(['variant_id' => $variant->id, 'stock_quantity' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

        $address = Address::create([
            'full_name' => 'Test Customer',
            'phone' => '0555222333',
            'wilaya_code' => Wilaya::firstOrCreate(
                ['code' => '16'],
                ['name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers'],
            )->code,
            'commune_id' => Commune::firstOrCreate(
                ['wilaya_code' => '16', 'name_ar' => 'الوسطى'],
                ['name_fr' => 'Centre', 'name_en' => 'Centre'],
            )->id,
            'address_line' => 'Rue Test',
        ]);

        return Order::create([
            'order_number' => 'CHB-TEST-'.Str::random(6),
            'address_id' => $address->id,
            'guest_name' => 'Test Customer',
            'guest_phone' => '0555222333',
            'delivery_method' => 'home',
            'payment_method' => 'cod',
            'order_status' => $status,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    public function test_dashboard_reports_real_order_totals(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');

        $this->makeOrder('pending', 100000);
        $this->makeOrder('delivered', 200000);
        $this->makeOrder('cancelled', 500000);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.orders_available', true)
            ->assertJsonPath('data.total_orders', 3)
            ->assertJsonPath('data.pending_orders', 1)
            // Cancelled order's 500000 is excluded from revenue.
            ->assertJsonPath('data.total_sales', 300000);
    }

    public function test_dashboard_requires_staff(): void
    {
        [, $headers] = $this->actingAsCustomer();

        $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_analytics_reports_daily_sales_top_products_and_status_breakdown(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Super Admin');

        $this->makeOrderWithItem('delivered', 100000, 'Amber Oud', 3);
        $this->makeOrderWithItem('delivered', 50000, 'Rose Musk', 1);
        $this->makeOrderWithItem('cancelled', 900000, 'Amber Oud', 5);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard/analytics');

        $response->assertStatus(200)
            ->assertJsonCount(30, 'data.sales_last_30_days')
            ->assertJsonPath('data.sales_last_30_days.29.date', now()->toDateString())
            // Only delivered orders count toward today's revenue — the
            // cancelled 900000 order is excluded.
            ->assertJsonPath('data.sales_last_30_days.29.total', 150000)
            ->assertJsonPath('data.top_products.0.name', 'Amber Oud')
            ->assertJsonPath('data.top_products.0.quantity_sold', 3);

        $statuses = collect($response->json('data.orders_by_status'))->pluck('count', 'status');
        $this->assertSame(2, $statuses['delivered']);
        $this->assertSame(1, $statuses['cancelled']);
    }

    public function test_analytics_requires_staff(): void
    {
        [, $headers] = $this->actingAsCustomer();

        $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard/analytics')->assertStatus(403);
    }
}

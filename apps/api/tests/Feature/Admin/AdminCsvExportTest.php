<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wilaya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminCsvExportTest extends TestCase
{
    use CreatesStaffUsers, RefreshDatabase;

    private function makeAddress(): Address
    {
        $wilaya = Wilaya::firstOrCreate(['code' => '16'], ['name_ar' => 'الجزائر', 'name_fr' => 'Alger', 'name_en' => 'Algiers']);
        $commune = Commune::firstOrCreate(
            ['wilaya_code' => $wilaya->code],
            ['name_ar' => 'الوسطى', 'name_fr' => 'Centre', 'name_en' => 'Centre'],
        );

        return Address::create([
            'full_name' => 'Amina Test', 'phone' => '+213555222333',
            'wilaya_code' => $wilaya->code, 'commune_id' => $commune->id, 'address_line' => 'Rue Test',
        ]);
    }

    public function test_product_manager_can_export_products_as_csv(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Product Manager');

        $category = Category::create([
            'name_ar' => 'فئة', 'name_fr' => 'Cat', 'name_en' => 'Cat',
            'slug' => 'cat-'.Str::random(8), 'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_fr' => 'Produit', 'name_en' => 'Amber Oud',
            'slug' => 'product-'.Str::random(8), 'base_price' => 250000, 'status' => 'active',
        ]);

        $response = $this->withHeaders($headers)->get('/api/v1/admin/products/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Amber Oud', $response->streamedContent());
    }

    public function test_a_role_without_products_view_cannot_export_products(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Customer Support');

        $this->withHeaders($headers)->get('/api/v1/admin/products/export')->assertStatus(403);
    }

    public function test_order_manager_can_export_orders_as_csv(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Order Manager');

        Order::create([
            'order_number' => 'CHB-2026-000123',
            'address_id' => $this->makeAddress()->id,
            'guest_name' => 'Amina Test', 'guest_phone' => '+213555222333', 'guest_email' => null,
            'delivery_method' => 'home', 'payment_method' => 'cod', 'payment_status' => 'pending',
            'order_status' => 'pending', 'subtotal' => 100000, 'discount_total' => 0,
            'delivery_fee' => 5000, 'tax_total' => 0, 'grand_total' => 105000,
        ]);

        $response = $this->withHeaders($headers)->get('/api/v1/admin/orders/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('CHB-2026-000123', $response->streamedContent());
    }

    public function test_customer_support_can_export_customers_as_csv(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Customer Support');

        User::create([
            'full_name' => 'Sara Customer',
            'email' => 'sara@example.com',
            'phone' => '+213555999888',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($headers)->get('/api/v1/admin/customers/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('Sara Customer', $response->streamedContent());
    }

    public function test_customer_show_now_includes_real_order_history(): void
    {
        $this->seedRbac();
        [, $headers] = $this->actingAsRole('Customer Support');

        $customer = User::create([
            'full_name' => 'Sara Customer',
            'phone' => '+213555999889',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);
        Order::create([
            'order_number' => 'CHB-2026-000124',
            'user_id' => $customer->id,
            'address_id' => $this->makeAddress()->id,
            'guest_name' => 'Sara Customer', 'guest_phone' => '+213555999889', 'guest_email' => null,
            'delivery_method' => 'home', 'payment_method' => 'cod', 'payment_status' => 'pending',
            'order_status' => 'pending', 'subtotal' => 100000, 'discount_total' => 0,
            'delivery_fee' => 5000, 'tax_total' => 0, 'grand_total' => 105000,
        ]);

        $response = $this->withHeaders($headers)->getJson("/api/v1/admin/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.orders_available', true)
            ->assertJsonPath('data.orders.0.order_number', 'CHB-2026-000124');
    }
}

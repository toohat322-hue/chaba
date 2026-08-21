<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_a_plain_customer_is_forbidden_from_every_admin_route(): void
    {
        [, $headers] = $this->actingAsCustomer();

        $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->withHeaders($headers)->getJson('/api/v1/admin/products')->assertStatus(403);
        $this->withHeaders($headers)->getJson('/api/v1/admin/customers')->assertStatus(403);
    }

    public function test_super_admin_can_reach_every_module(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');

        $this->withHeaders($headers)->getJson('/api/v1/admin/dashboard')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/admin/products')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/admin/categories')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/admin/customers')->assertStatus(200);
    }

    public function test_inventory_manager_can_view_products_but_not_create_them(): void
    {
        [, $headers] = $this->actingAsRole('Inventory Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/products')->assertStatus(200);

        $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'name_ar' => 'x', 'name_fr' => 'x', 'name_en' => 'x',
        ])->assertStatus(403);
    }

    public function test_inventory_manager_cannot_view_customers(): void
    {
        [, $headers] = $this->actingAsRole('Inventory Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/customers')->assertStatus(403);
    }

    public function test_product_manager_can_manage_products_and_categories_but_not_customers(): void
    {
        [, $headers] = $this->actingAsRole('Product Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/products')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/admin/categories')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/v1/admin/customers')->assertStatus(403);
    }

    public function test_customer_support_can_manage_customers_but_not_products(): void
    {
        [, $headers] = $this->actingAsRole('Customer Support');

        $this->withHeaders($headers)->getJson('/api/v1/admin/customers')->assertStatus(200);

        $this->withHeaders($headers)->postJson('/api/v1/admin/products', [
            'name_ar' => 'x', 'name_fr' => 'x', 'name_en' => 'x',
        ])->assertStatus(403);
    }
}

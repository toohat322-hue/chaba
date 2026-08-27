<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_super_admin_can_list_roles_with_their_permissions(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/roles');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Order Manager', $names);

        $orderManager = collect($response->json('data'))->firstWhere('name', 'Order Manager');
        $this->assertContains('orders.view', $orderManager['permission_keys']);
        $this->assertNotContains('payments.view', $orderManager['permission_keys']);
    }

    public function test_the_permission_catalog_can_be_listed(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200);
        $keys = collect($response->json('data'))->pluck('key');
        $this->assertContains('payments.reconcile', $keys);
        $this->assertGreaterThanOrEqual(30, $keys->count());
    }

    public function test_super_admin_can_grant_a_new_permission_to_a_role(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $role = Role::where('name', 'Customer Support')->firstOrFail();

        $current = $this->withHeaders($headers)->getJson("/api/v1/admin/roles/{$role->id}")->json('data.permission_keys');
        $updated = [...$current, 'coupons.view'];

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/roles/{$role->id}", [
            'permission_keys' => $updated,
        ]);

        $response->assertStatus(200);
        $this->assertContains('coupons.view', $response->json('data.permission_keys'));
    }

    public function test_the_super_admin_role_itself_cannot_be_edited(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $role = Role::where('name', 'Super Admin')->firstOrFail();

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/roles/{$role->id}", ['permission_keys' => []])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'role_not_editable');
    }

    /**
     * Regression: RolePermissionSeeder runs on every deploy — it must never
     * reset a role's permissions back to the hardcoded defaults once an
     * admin has customized them (previously deleted and re-inserted every
     * role's grants unconditionally on every run).
     */
    public function test_seeding_again_does_not_undo_an_admin_customized_roles_permissions(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $role = Role::where('name', 'Customer Support')->firstOrFail();

        $this->withHeaders($headers)->patchJson("/api/v1/admin/roles/{$role->id}", [
            'permission_keys' => ['orders.view'],
        ])->assertStatus(200);

        (new RolePermissionSeeder)->run();

        $keys = $role->fresh('permissions')->permissions->pluck('key')->all();
        $this->assertSame(['orders.view'], $keys);
    }

    public function test_seeding_again_still_applies_to_a_role_that_was_never_customized(): void
    {
        $role = Role::where('name', 'Order Manager')->firstOrFail();
        $role->permissions()->detach();

        (new RolePermissionSeeder)->run();

        $keys = $role->fresh('permissions')->permissions->pluck('key')->all();
        $this->assertContains('orders.view', $keys);
    }

    public function test_a_role_without_roles_view_is_forbidden(): void
    {
        [, $headers] = $this->actingAsRole('Order Manager');

        $this->withHeaders($headers)->getJson('/api/v1/admin/roles')->assertStatus(403);
    }

    public function test_super_admin_can_create_a_new_staff_member(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $role = Role::where('name', 'Customer Support')->firstOrFail();

        $response = $this->withHeaders($headers)->postJson('/api/v1/admin/staff', [
            'full_name' => 'New Support Agent',
            'phone' => '0555444555',
            'password' => 'StaffPass123',
            'password_confirmation' => 'StaffPass123',
            'role_id' => $role->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'New Support Agent')
            ->assertJsonPath('data.role.name', 'Customer Support');
    }

    public function test_staff_list_never_includes_plain_customers(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        $customer = $this->makeCustomer();

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/staff');

        $response->assertStatus(200);
        $phones = collect($response->json('data.items'))->pluck('phone');
        $this->assertNotContains($customer->phone, $phones);
    }

    public function test_super_admin_can_change_another_staff_members_role(): void
    {
        [, $headers] = $this->actingAsRole('Super Admin');
        [$staff] = $this->actingAsRole('Customer Support');
        $newRole = Role::where('name', 'Marketing Manager')->firstOrFail();

        $response = $this->withHeaders($headers)->patchJson("/api/v1/admin/staff/{$staff->id}", [
            'role_id' => $newRole->id,
        ]);

        $response->assertStatus(200)->assertJsonPath('data.role.name', 'Marketing Manager');
    }

    public function test_a_super_admin_cannot_change_their_own_role_or_status(): void
    {
        [$user, $headers] = $this->actingAsRole('Super Admin');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/staff/{$user->id}", ['status' => 'blocked'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'cannot_modify_self');
    }

    private function makeCustomer(): User
    {
        return User::create([
            'full_name' => 'Plain Customer',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt('password123'), 'status' => 'active',
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// PRD §19.1 table translated into permission-key grants per role.
//
// Runs on every deploy (ProductionSeeder, via preDeployCommand) — an admin
// can customize a role's permission set (RoleController::update), so this
// must never reset one back to these hardcoded defaults after that happens.
// roles.permissions_customized_at (set by that same update()) marks a role
// as admin-owned; only roles still at null (never touched, or freshly
// created by RoleSeeder) get kept in sync with this table as it evolves.
class RolePermissionSeeder extends Seeder
{
    private const GRANTS = [
        'Super Admin' => ['*'],
        'Admin' => ['*_except_' => ['roles.view', 'roles.edit', 'settings.payment_providers', 'audit_logs.view']],
        'Order Manager' => [
            'orders.view', 'orders.edit', 'orders.refund',
            'delivery.view', 'delivery.edit',
            'customers.view',
            'inventory.view',
        ],
        'Product Manager' => [
            'products.view', 'products.edit', 'products.delete',
            'categories.view', 'categories.edit', 'categories.delete',
            'inventory.view',
            'reviews.view', 'reviews.moderate',
        ],
        'Inventory Manager' => [
            'inventory.view', 'inventory.adjust',
            'products.view',
        ],
        'Customer Support' => [
            'orders.view', 'orders.edit',
            'customers.view', 'customers.edit',
            'notifications.view',
            'reviews.view',
        ],
        'Marketing Manager' => [
            'coupons.view', 'coupons.edit', 'coupons.delete',
            'reports.view',
            'notifications.view', 'notifications.edit',
            'footer.view', 'footer.edit',
            'hero_slides.view', 'hero_slides.edit',
        ],
        'Finance Manager' => [
            'payments.view', 'payments.edit', 'payments.reconcile',
            'reports.view', 'reports.export',
        ],
    ];

    public function run(): void
    {
        $roles = DB::table('roles')->whereNull('permissions_customized_at')->pluck('id', 'name');
        $permissionIds = DB::table('permissions')->pluck('id', 'key');
        $allPermissionKeys = $permissionIds->keys();

        foreach (self::GRANTS as $roleName => $grant) {
            $roleId = $roles[$roleName] ?? null;
            if (! $roleId) {
                continue;
            }

            $keys = match (true) {
                $grant === ['*'] => $allPermissionKeys,
                isset($grant['*_except_']) => $allPermissionKeys->diff($grant['*_except_']),
                default => collect($grant),
            };

            DB::table('role_permissions')->where('role_id', $roleId)->delete();

            $rows = $keys
                ->map(fn ($key) => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$key] ?? null,
                ])
                ->filter(fn ($row) => $row['permission_id'] !== null)
                ->values()
                ->all();

            if ($rows !== []) {
                DB::table('role_permissions')->insert($rows);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// PRD §19.1: the 8 admin roles.
class RoleSeeder extends Seeder
{
    public const ROLES = [
        'Super Admin' => 'Full access to all modules including Settings, Roles & Permissions, and payment provider configuration.',
        'Admin' => 'Full access except Roles & Permissions and payment provider secrets.',
        'Order Manager' => 'Orders (full), Delivery (full), Customers (view), Inventory (view).',
        'Product Manager' => 'Products (full), Categories (full), Inventory (view), Reviews (moderate).',
        'Inventory Manager' => 'Inventory (full incl. adjustments), Products (view).',
        'Customer Support' => 'Orders (view + status update + notes), Customers (full), Notifications (view), Reviews (view).',
        'Marketing Manager' => 'Coupons (full), Promotions (full), Reports - sales/coupon (view), Notifications templates (full).',
        'Finance Manager' => 'Payments (full), Reports - all (view/export), Reconciliation (full).',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// PRD §19.1: permission keys behind the admin modules each role can touch.
class PermissionSeeder extends Seeder
{
    public const KEYS = [
        'products.view' => 'View products',
        'products.edit' => 'Create/update products',
        'products.delete' => 'Delete/archive products',
        'categories.view' => 'View categories',
        'categories.edit' => 'Create/update categories',
        'categories.delete' => 'Delete categories',
        'inventory.view' => 'View stock levels',
        'inventory.adjust' => 'Adjust stock quantities',
        'orders.view' => 'View orders',
        'orders.edit' => 'Update order status/notes',
        'orders.refund' => 'Initiate refunds',
        'delivery.view' => 'View shipments',
        'delivery.edit' => 'Manage courier config/shipments',
        'customers.view' => 'View customer profiles',
        'customers.edit' => 'Block/unblock, edit customer notes',
        'coupons.view' => 'View coupons',
        'coupons.edit' => 'Create/update coupons',
        'coupons.delete' => 'Delete/deactivate coupons',
        'reviews.view' => 'View reviews',
        'reviews.moderate' => 'Approve/reject reviews',
        'notifications.view' => 'View notification logs',
        'notifications.edit' => 'Manage notification templates',
        'reports.view' => 'View reports',
        'reports.export' => 'Export reports',
        'payments.view' => 'View transactions',
        'payments.edit' => 'Process refunds/payment records',
        'payments.reconcile' => 'Run reconciliation',
        'settings.view' => 'View store settings',
        'settings.edit' => 'Edit store settings',
        'settings.payment_providers' => 'Configure payment provider secrets',
        'footer.view' => 'View footer content (features, links, social, payment methods)',
        'footer.edit' => 'Manage footer content and newsletter subscribers',
        'hero_slides.view' => 'View the homepage hero slider',
        'hero_slides.edit' => 'Manage the homepage hero slider',
        'roles.view' => 'View roles & permissions',
        'roles.edit' => 'Edit roles & permissions',
        'audit_logs.view' => 'View the admin audit log',
    ];

    public function run(): void
    {
        foreach (self::KEYS as $key => $description) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['description' => $description],
            );
        }
    }
}

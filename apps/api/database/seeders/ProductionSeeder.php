<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Everything DatabaseSeeder runs *except* CategorySeeder/ProductSeeder, which
// are sample catalog data meant for local dev/demo, not a real store. Every
// seeder here is reference data or structural setup the app genuinely needs
// to function (wilayas/communes/delivery fees, RBAC roles+permissions, the
// first Super Admin, and FooterContentSeeder's real starter about/trust-
// badge copy — not fake data, see its own docblock). Run this instead of
// `db:seed` when deploying to a real environment; run plain `db:seed` (which
// includes this) for local dev, where the sample catalog is actually wanted.
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayaSeeder::class,
            CommuneSeeder::class,
            DeliveryFeeSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            FooterContentSeeder::class,
        ]);
    }
}

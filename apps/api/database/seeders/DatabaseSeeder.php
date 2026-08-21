<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
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
            CategorySeeder::class,
            ProductSeeder::class,
            FooterContentSeeder::class,
        ]);
    }
}

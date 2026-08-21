<?php

namespace Database\Seeders;

use App\Support\PhoneNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Dev-only Super Admin so the admin API/dashboard is reachable immediately
// after `migrate:fresh --seed`. Credentials come from env vars — never
// baked into a value that would ship to production.
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = PhoneNormalizer::toE164(env('ADMIN_SEED_PHONE', '0555000000'));
        $password = env('ADMIN_SEED_PASSWORD', 'ChangeMe123!');

        if (! $phone) {
            $this->command?->warn('ADMIN_SEED_PHONE is not a valid Algerian number; skipping AdminUserSeeder.');

            return;
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');

        DB::table('users')->updateOrInsert(
            ['phone' => $phone],
            [
                'full_name' => 'CHABA Admin',
                'password_hash' => Hash::make($password),
                'role_id' => $roleId,
                'preferred_language' => 'ar',
                'is_guest' => false,
                'phone_verified_at' => now(),
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}

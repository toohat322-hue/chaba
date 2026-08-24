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
        $rawPhone = env('ADMIN_SEED_PHONE');
        $password = env('ADMIN_SEED_PASSWORD');
        // Optional — phone stays the required identity (LoginController falls
        // back to an email lookup only when the login value doesn't parse as
        // an Algerian phone), this just lets the same admin sign in with
        // either one.
        $email = env('ADMIN_SEED_EMAIL');

        if (! $rawPhone || ! $password) {
            $this->command?->warn('ADMIN_SEED_PHONE / ADMIN_SEED_PASSWORD are not set; skipping AdminUserSeeder.');

            return;
        }

        $phone = PhoneNormalizer::toE164($rawPhone);

        if (! $phone) {
            $this->command?->warn('ADMIN_SEED_PHONE is not a valid Algerian number; skipping AdminUserSeeder.');

            return;
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');

        DB::table('users')->updateOrInsert(
            ['phone' => $phone],
            [
                'full_name' => 'CHABA Admin',
                'email' => $email ?: null,
                'password_hash' => Hash::make($password),
                'role_id' => $roleId,
                'preferred_language' => 'ar',
                'is_guest' => false,
                'phone_verified_at' => now(),
                'email_verified_at' => $email ? now() : null,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}

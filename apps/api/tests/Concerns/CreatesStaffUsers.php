<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;
use App\Services\TokenService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

/**
 * Reuses the real Phase 2 RBAC seeders (8 roles, 34 permissions, mapped
 * exactly per PRD §19.1) rather than hand-rolling fake roles per test —
 * admin access tests are only meaningful against the real permission grants.
 */
trait CreatesStaffUsers
{
    protected function seedRbac(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    /** @return array{0: User, 1: array<string, string>} */
    protected function actingAsRole(string $roleName): array
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::create([
            'full_name' => $roleName.' Test User',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt(Str::random(16)),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }

    /** @return array{0: User, 1: array<string, string>} */
    protected function actingAsCustomer(): array
    {
        $user = User::create([
            'full_name' => 'Plain Customer',
            'phone' => '+2135'.substr(str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), 0, 8),
            'password_hash' => bcrypt(Str::random(16)),
            'status' => 'active',
        ]);

        $pair = app(TokenService::class)->issuePair($user);

        return [$user, ['Authorization' => "Bearer {$pair['access_token']}"]];
    }
}

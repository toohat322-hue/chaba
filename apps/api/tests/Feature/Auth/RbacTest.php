<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * No admin routes exist yet (Phase 7) — this registers a throwaway route
 * behind `permission:orders.edit` at runtime purely to prove EnsurePermission
 * itself works correctly, ahead of real admin endpoints using it.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:sanctum', 'abilities:access-api', 'permission:orders.edit'])
            ->get('/api/v1/_test/orders-edit-only', fn () => response()->json(['success' => true]));
    }

    private function authHeader(User $user): array
    {
        $pair = app(TokenService::class)->issuePair($user);

        return ['Authorization' => "Bearer {$pair['access_token']}"];
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $user = User::create([
            'full_name' => 'Plain Customer',
            'phone' => '+213555777888',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->withHeaders($this->authHeader($user))
            ->getJson('/api/v1/_test/orders-edit-only')
            ->assertStatus(403);
    }

    public function test_a_user_with_the_permission_via_their_role_is_allowed(): void
    {
        $permission = Permission::create(['key' => 'orders.edit', 'description' => 'test']);
        $role = Role::create(['name' => 'Order Manager Test', 'description' => 'test']);
        $role->permissions()->attach($permission);

        $user = User::forceCreate([
            'full_name' => 'Order Manager',
            'phone' => '+213555777889',
            'password_hash' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->withHeaders($this->authHeader($user))
            ->getJson('/api/v1/_test/orders-edit-only')
            ->assertStatus(200);
    }
}

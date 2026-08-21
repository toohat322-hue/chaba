<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;

// PRD §19.1/§25 Phase 10: role/permission management. Gated behind
// roles.view/roles.edit — the two permission keys RolePermissionSeeder
// deliberately never grants to the "Admin" role, only "Super Admin" — the
// same route-middleware protection every other admin module gets, not a
// frontend-only hidden button.
class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return ApiResponse::success($roles->map(fn (Role $role) => $this->serialize($role)));
    }

    public function show(string $role): JsonResponse
    {
        $model = Role::with('permissions')->withCount('users')->find($role);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Role not found.');
        }

        return ApiResponse::success($this->serialize($model));
    }

    public function update(UpdateRolePermissionsRequest $request, string $role): JsonResponse
    {
        $model = Role::find($role);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Role not found.');
        }

        // Super Admin always has full access by definition (RolePermissionSeeder
        // grants it ['*']) — letting it be edited here risks a self-lockout
        // with no recovery path short of re-seeding the database.
        if ($model->name === 'Super Admin') {
            throw ApiException::businessRule(
                'role_not_editable',
                'The Super Admin role always has full access and cannot be modified.',
            );
        }

        $beforeKeys = $model->permissions->pluck('key')->sort()->values()->all();

        $permissionIds = Permission::whereIn('key', $request->input('permission_keys', []))->pluck('id');
        $model->permissions()->sync($permissionIds);

        $refreshed = $model->fresh('permissions')->loadCount('users');
        $afterKeys = $refreshed->permissions->pluck('key')->sort()->values()->all();

        AuditLogger::log($request->user(), 'role.permissions_updated', $model, ['permission_keys' => [$beforeKeys, $afterKeys]]);

        return ApiResponse::success($this->serialize($refreshed));
    }

    private function serialize(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'staff_count' => $role->users_count,
            'permission_keys' => $role->permissions->pluck('key')->values(),
        ];
    }
}

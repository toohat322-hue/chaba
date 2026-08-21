<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// Full permission catalog (PermissionSeeder is the canonical seed source,
// this just reads what's actually in the DB) — the admin UI renders this as
// the checkbox list for RoleController::update.
class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            Permission::orderBy('key')->get(['id', 'key', 'description']),
        );
    }
}

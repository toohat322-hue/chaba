<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Mirrors Admin\CustomerController exactly, scoped to the other side of the
// same role_id-nullable split (staff = role_id set, customers = null — see
// the comment in CustomerController::index()).
class StaffController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $query = User::whereNotNull('role_id')->with('role');

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->string('role_id'));
        }

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('full_name', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => collect($paginator->items())->map(fn ($user) => $this->serialize($user)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $user = User::create([
            'full_name' => $request->string('full_name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->email,
            'password_hash' => Hash::make($request->string('password')->toString()),
            'role_id' => $request->input('role_id'),
            'is_guest' => false,
            'status' => 'active',
        ]);

        AuditLogger::log($request->user(), 'staff.created', $user);

        return ApiResponse::success($this->serialize($user->fresh('role')), 201);
    }

    public function update(UpdateStaffRequest $request, string $staff): JsonResponse
    {
        $user = User::whereNotNull('role_id')->with('role')->find($staff);

        if (! $user) {
            throw new ApiException('not_found', 'Staff member not found.', 404);
        }

        // A Super Admin can't demote/block themself — avoids a full
        // self-lockout with no recovery path short of re-seeding.
        if ($user->role?->name === 'Super Admin' && $request->user()->id === $user->id) {
            throw ApiException::businessRule('cannot_modify_self', 'You cannot change your own role or status.');
        }

        $user->fill($request->only(['role_id', 'status']));
        $changes = AuditLogger::diff($user);
        $user->save();

        AuditLogger::log($request->user(), 'staff.updated', $user, $changes);

        if ($request->input('status') === 'blocked') {
            $user->tokens()->delete();
        }

        return ApiResponse::success($this->serialize($user->fresh('role')));
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->role ? ['id' => $user->role->id, 'name' => $user->role->name] : null,
            'created_at' => $user->created_at,
        ];
    }
}

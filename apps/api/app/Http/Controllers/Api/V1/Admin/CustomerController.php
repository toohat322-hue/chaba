<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\CsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        // role_id is null for customers — staff accounts (role_id set) are
        // a separate concept and never listed here.
        $query = User::whereNull('role_id');

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

    public function show(string $customer): JsonResponse
    {
        $user = User::whereNull('role_id')->find($customer);

        if (! $user) {
            throw new ApiException('not_found', 'Customer not found.', 404);
        }

        // Orders/Payments were built in a later phase but this endpoint was
        // never revisited to reflect it (same class of gap as
        // DashboardController's orders_available) — this was reporting a
        // permanent false/empty even after orders existed and worked.
        $orders = Order::where('user_id', $user->id)->orderByDesc('created_at')->get();

        return ApiResponse::success([
            ...$this->serialize($user),
            'orders_available' => true,
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = User::whereNull('role_id');

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('full_name', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        }

        return CsvExport::stream(
            'customers.csv',
            ['Name', 'Phone', 'Email', 'Status', 'Joined At'],
            (function () use ($query) {
                foreach ($query->orderBy('created_at', 'desc')->cursor() as $user) {
                    yield [
                        $user->full_name,
                        $user->phone,
                        $user->email,
                        $user->status,
                        $user->created_at?->toDateTimeString(),
                    ];
                }
            })(),
        );
    }

    public function update(UpdateCustomerRequest $request, string $customer): JsonResponse
    {
        $user = User::whereNull('role_id')->find($customer);

        if (! $user) {
            throw new ApiException('not_found', 'Customer not found.', 404);
        }

        $user->update(['status' => $request->input('status')]);

        if ($request->input('status') === 'blocked') {
            $user->tokens()->delete();
        }

        return ApiResponse::success($this->serialize($user));
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $user->status,
            'phone_verified' => $user->phone_verified_at !== null,
            'created_at' => $user->created_at,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('q')) {
            $query->where('code', 'ILIKE', '%'.$request->string('q').'%');
        }

        $paginator = $query->orderBy('code')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => array_map(fn ($coupon) => $this->present($coupon), $paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        AuditLogger::log($request->user(), 'coupon.created', $coupon);

        return ApiResponse::success($this->present($coupon->loadCount('usages')), 201);
    }

    public function show(string $coupon): JsonResponse
    {
        $model = Coupon::withCount('usages')->find($coupon);

        if (! $model) {
            throw new ApiException('not_found', 'Coupon not found.', 404);
        }

        return ApiResponse::success($this->present($model));
    }

    public function update(UpdateCouponRequest $request, string $coupon): JsonResponse
    {
        $model = Coupon::find($coupon);

        if (! $model) {
            throw new ApiException('not_found', 'Coupon not found.', 404);
        }

        $model->fill($request->validated());
        $changes = AuditLogger::diff($model);
        $model->save();

        AuditLogger::log($request->user(), 'coupon.updated', $model, $changes);

        return ApiResponse::success($this->present($model->loadCount('usages')));
    }

    /**
     * Deactivates rather than deletes — coupon_usages cascade-deletes on the
     * coupon, and usage/redemption history must never be lost (same
     * soft-archive discipline as Admin\ProductController::destroy()).
     */
    public function destroy(Request $request, string $coupon): JsonResponse
    {
        $model = Coupon::find($coupon);

        if (! $model) {
            throw new ApiException('not_found', 'Coupon not found.', 404);
        }

        $model->update(['is_active' => false]);

        AuditLogger::log($request->user(), 'coupon.deactivated', $model);

        return ApiResponse::success(['message' => 'Coupon deactivated.']);
    }

    private function present(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'min_order_value' => $coupon->min_order_value,
            'start_date' => $coupon->start_date,
            'end_date' => $coupon->end_date,
            'usage_limit_total' => $coupon->usage_limit_total,
            'usage_limit_per_customer' => $coupon->usage_limit_per_customer,
            'usage_count' => $coupon->usages_count,
            'is_active' => $coupon->is_active,
        ];
    }
}

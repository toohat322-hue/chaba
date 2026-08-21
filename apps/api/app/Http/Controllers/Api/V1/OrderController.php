<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $paginator = $request->user()->orders()
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => OrderResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Looked up by order_number (globally unique), not id — the frontend
     * uses the human-readable order number in the URL everywhere an order
     * is linked to (confirmation page, My Orders), never the opaque UUID.
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $model = $request->user()->orders()
            ->with(['items', 'address.wilaya', 'address.commune', 'statusHistory', 'payments', 'shipments', 'coupon'])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $model) {
            throw new ApiException('not_found', 'Order not found.', 404);
        }

        return ApiResponse::success(new OrderResource($model));
    }
}

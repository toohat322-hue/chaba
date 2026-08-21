<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\CsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with('address');

        if ($request->filled('status')) {
            $query->where('order_status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('order_number', 'ILIKE', $like)
                    ->orWhere('guest_name', 'ILIKE', $like)
                    ->orWhere('guest_phone', 'ILIKE', $like);
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

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

    public function show(string $order): JsonResponse
    {
        $model = Order::with(['items', 'address.wilaya', 'address.commune', 'statusHistory', 'payments', 'shipments', 'coupon'])->find($order);

        if (! $model) {
            throw new ApiException('not_found', 'Order not found.', 404);
        }

        return ApiResponse::success(new OrderResource($model));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Order::query();

        if ($request->filled('status')) {
            $query->where('order_status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('order_number', 'ILIKE', $like)
                    ->orWhere('guest_name', 'ILIKE', $like)
                    ->orWhere('guest_phone', 'ILIKE', $like);
            });
        }

        return CsvExport::stream(
            'orders.csv',
            ['Order Number', 'Customer', 'Phone', 'Status', 'Payment Method', 'Payment Status', 'Grand Total (DZD)', 'Placed At'],
            (function () use ($query) {
                foreach ($query->orderByDesc('created_at')->cursor() as $order) {
                    yield [
                        $order->order_number,
                        $order->guest_name,
                        $order->guest_phone,
                        $order->order_status,
                        $order->payment_method,
                        $order->payment_status,
                        round($order->grand_total / 100, 2),
                        $order->created_at?->toDateTimeString(),
                    ];
                }
            })(),
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $order): JsonResponse
    {
        $model = Order::find($order);

        if (! $model) {
            throw new ApiException('not_found', 'Order not found.', 404);
        }

        $previousStatus = $model->order_status;

        $updated = $this->orders->updateStatus(
            $model,
            $request->string('status')->toString(),
            $request->input('note'),
            $request->user(),
        );

        AuditLogger::log(
            $request->user(),
            'order.status_changed',
            $updated,
            ['order_status' => [$previousStatus, $updated->order_status]],
        );

        return ApiResponse::success(new OrderResource($updated));
    }
}

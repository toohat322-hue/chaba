<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use App\Support\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);

        $result = $this->orders->checkout($cart, $request->validated(), $request->user('sanctum'));

        return ApiResponse::success([
            'order' => new OrderResource($result['order']),
            'whatsapp' => $result['whatsapp'],
        ], 201);
    }

    /**
     * Best-effort signal that the customer's browser attempted the wa.me
     * redirect — never confirms the order (order_status is untouched here);
     * only a human admin or a future real webhook does that. Guest-safe,
     * same non-leaking phone-match guard as OrderTrackController::show.
     */
    public function whatsappOpened(Request $request, string $orderNumber): JsonResponse
    {
        $phone = PhoneNormalizer::toE164((string) $request->input('phone', ''));

        $order = $phone
            ? Order::where('order_number', $orderNumber)->where('guest_phone', $phone)->first()
            : null;

        if (! $order) {
            throw new ApiException('not_found', 'Order not found.', 404);
        }

        $order->update(['whatsapp_status' => 'opened']);

        return ApiResponse::success(['whatsapp_status' => $order->whatsapp_status]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use App\Support\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackController extends Controller
{
    /**
     * Public guest order lookup (PRD §7.7 step 6 "tracking link" — no real
     * SMS/email deep-link system exists yet, so order_number + phone is the
     * lookup key). Never reveals whether the order_number exists at all if
     * the phone doesn't match, to avoid leaking one guest's order to another.
     */
    public function show(Request $request): JsonResponse
    {
        $orderNumber = $request->string('order_number')->toString();
        $phone = PhoneNormalizer::toE164($request->string('phone')->toString());

        $order = $phone
            ? Order::with(['items', 'address.wilaya', 'address.commune', 'statusHistory', 'payments', 'shipments', 'coupon'])
                ->where('order_number', $orderNumber)
                ->where('guest_phone', $phone)
                ->first()
            : null;

        if (! $order) {
            throw new ApiException('not_found', 'No order found matching that order number and phone.', 404);
        }

        return ApiResponse::success(new OrderResource($order));
    }
}

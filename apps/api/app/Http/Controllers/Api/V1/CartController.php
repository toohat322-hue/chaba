<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\ApplyCouponRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CouponService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CouponService $coupons,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);

        return ApiResponse::success(new CartResource($this->loadCart($cart)));
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);

        $this->cart->addItem($cart, $request->string('variant_id')->toString(), (int) $request->input('quantity'));

        return ApiResponse::success(new CartResource($this->loadCart($cart)), 201);
    }

    public function update(UpdateCartItemRequest $request, string $item): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);
        $cartItem = $cart->items()->find($item);

        if (! $cartItem) {
            throw new ApiException('not_found', 'Cart item not found.', 404);
        }

        $this->cart->updateItem($cartItem, (int) $request->input('quantity'));

        return ApiResponse::success(new CartResource($this->loadCart($cart)));
    }

    public function destroy(Request $request, string $item): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);
        $cartItem = $cart->items()->find($item);

        if (! $cartItem) {
            throw new ApiException('not_found', 'Cart item not found.', 404);
        }

        $this->cart->removeItem($cartItem);

        return ApiResponse::success(new CartResource($this->loadCart($cart)));
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);
        $loaded = $this->loadCart($cart);

        $subtotal = $loaded->items->sum(fn ($item) => $item->variant->effectivePrice() * $item->quantity);

        $coupon = $this->coupons->findValid(
            $request->string('code')->toString(),
            $subtotal,
            $request->user('sanctum'),
        );

        $cart->update(['coupon_code' => $coupon->code]);

        return ApiResponse::success(new CartResource($loaded));
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cart->resolveCart($request);
        $cart->update(['coupon_code' => null]);

        return ApiResponse::success(new CartResource($this->loadCart($cart)));
    }

    /**
     * Called by the frontend right after a login/register/social-login
     * succeeds, while a guest session token is still known client-side —
     * folds that guest cart's items into the now-authenticated user's cart.
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate(['guest_session_token' => ['required', 'string']]);

        $cart = $this->cart->resolveCart($request);
        $this->cart->mergeGuestCart($cart, $request->string('guest_session_token')->toString());

        return ApiResponse::success(new CartResource($this->loadCart($cart)));
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load(['items.variant.product.images', 'items.variant.inventory']);
    }
}

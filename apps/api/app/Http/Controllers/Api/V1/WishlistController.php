<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\Product;
use App\Services\WishlistService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PRD §25 Phase 3: same guest/auth duality as Cart (no auth:sanctum
// requirement — WishlistService::resolveIdentity handles both via
// X-Guest-Session or token), intentionally not merged on login yet, same
// known/deferred gap already documented for the cart in lib/api.ts.
class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(WishlistItemResource::collection($this->wishlist->resolveItems($request)));
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        $product = Product::find($request->validated('product_id'));

        $item = $this->wishlist->addProduct($request, $product);
        $item->load('variant.product.images', 'variant.product.variants.inventory');

        return ApiResponse::success(new WishlistItemResource($item), 201);
    }

    public function destroy(Request $request, string $product): JsonResponse
    {
        $model = Product::find($product);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Product not found.');
        }

        $this->wishlist->removeProduct($request, $model);

        return ApiResponse::success(['message' => 'Removed from wishlist.']);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->wishlist->clear($request);

        return ApiResponse::success(['message' => 'Wishlist cleared.']);
    }
}

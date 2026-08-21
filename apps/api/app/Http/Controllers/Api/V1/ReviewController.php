<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request, string $product): JsonResponse
    {
        $productModel = $this->findProduct($product);

        $paginator = Review::where('product_id', $productModel->id)
            ->where('status', 'approved')
            ->with(['user', 'images'])
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => ReviewResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function mine(Request $request, string $product): JsonResponse
    {
        $productModel = $this->findProduct($product);

        $review = Review::where('product_id', $productModel->id)
            ->where('user_id', $request->user()->id)
            ->with('images')
            ->first();

        return ApiResponse::success($review ? new ReviewResource($review) : null);
    }

    public function store(StoreReviewRequest $request, string $product): JsonResponse
    {
        $productModel = $this->findProduct($product);

        $review = $this->reviews->submit($productModel, $request->user(), $request->validated());

        return ApiResponse::success(new ReviewResource($review), 201);
    }

    public function update(UpdateReviewRequest $request, string $review): JsonResponse
    {
        $model = $this->findOwnReview($request, $review);

        $updated = $this->reviews->update($model, $request->validated());

        return ApiResponse::success(new ReviewResource($updated));
    }

    public function destroy(Request $request, string $review): JsonResponse
    {
        $model = $this->findOwnReview($request, $review);

        $this->reviews->delete($model);

        return ApiResponse::success(['message' => 'Review deleted.']);
    }

    private function findProduct(string $slug): Product
    {
        $product = Product::where('slug', $slug)->where('status', 'active')->first();

        if (! $product) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        return $product;
    }

    private function findOwnReview(Request $request, string $reviewId): Review
    {
        $review = Review::where('user_id', $request->user()->id)->find($reviewId);

        if (! $review) {
            throw new ApiException('not_found', 'Review not found.', 404);
        }

        return $review;
    }
}

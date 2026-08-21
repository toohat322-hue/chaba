<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'product', 'images'])->withCount('reports');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(fn (Review $review) => [
                ...(new ReviewResource($review))->resolve(),
                'product' => ['id' => $review->product->id, 'slug' => $review->product->slug, 'name' => [
                    'ar' => $review->product->name_ar, 'fr' => $review->product->name_fr, 'en' => $review->product->name_en,
                ]],
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function updateStatus(Request $request, string $review): JsonResponse
    {
        $model = Review::find($review);

        if (! $model) {
            throw new ApiException('not_found', 'Review not found.', 404);
        }

        $status = $request->string('status')->toString();

        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ApiException::businessRule('invalid_status', 'Status must be approved or rejected.');
        }

        $updated = $this->reviews->updateStatus($model, $status);

        return ApiResponse::success(new ReviewResource($updated));
    }
}

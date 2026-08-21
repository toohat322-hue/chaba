<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewReportRequest;
use App\Models\Review;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReviewReportController extends Controller
{
    public function store(StoreReviewReportRequest $request, string $review): JsonResponse
    {
        $reviewModel = Review::find($review);

        if (! $reviewModel) {
            throw new ApiException('not_found', 'Review not found.', 404);
        }

        $reviewModel->reports()->create([
            'reporter_user_id' => $request->user()->id,
            'reason' => $request->string('reason')->toString(),
            'created_at' => now(),
        ]);

        return ApiResponse::success(['message' => 'Report submitted.'], 201);
    }
}

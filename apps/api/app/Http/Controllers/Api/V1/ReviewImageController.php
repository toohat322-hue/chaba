<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewImageRequest;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Support\ApiResponse;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewImageController extends Controller
{
    // PRD §7.12: up to 5 photos per review.
    private const MAX_IMAGES = 5;

    public function store(StoreReviewImageRequest $request, string $review): JsonResponse
    {
        $reviewModel = $this->findOwnReview($request, $review);

        if ($reviewModel->images()->count() >= self::MAX_IMAGES) {
            throw ApiException::businessRule('too_many_images', 'A review can have at most 5 photos.');
        }

        $file = $request->file('image');
        $path = 'reviews/'.$reviewModel->id.'/'.Str::uuid().'.'.$file->extension();

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');

        $image = ReviewImage::create([
            'review_id' => $reviewModel->id,
            'url' => Storage::disk('s3')->url($path),
        ]);

        return ApiResponse::success(['id' => $image->id, 'url' => $image->url], 201);
    }

    public function destroy(Request $request, string $review, string $image): JsonResponse
    {
        $reviewModel = $this->findOwnReview($request, $review);

        $imageModel = ReviewImage::where('review_id', $reviewModel->id)->find($image);

        if (! $imageModel) {
            throw new ApiException('not_found', 'Image not found.', 404);
        }

        $key = MediaUrl::key($imageModel->getRawOriginal('url'));

        if ($key) {
            Storage::disk('s3')->delete($key);
        }

        $imageModel->delete();

        return ApiResponse::success(['message' => 'Image deleted.']);
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

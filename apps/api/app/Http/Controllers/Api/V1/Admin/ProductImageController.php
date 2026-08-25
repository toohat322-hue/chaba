<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderProductImagesRequest;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Http\Requests\Admin\UpdateProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ApiResponse;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, string $product): JsonResponse
    {
        $productModel = Product::find($product);

        if (! $productModel) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $file = $request->file('image');
        // Slug-derived path (not the raw UUID alone) so uploaded image URLs
        // carry a real product-name signal for Google Images SEO; the UUID
        // still guarantees uniqueness. Existing images keep their old URLs.
        $path = 'products/'.$productModel->slug.'/'.Str::uuid().'.'.$file->extension();

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');

        $image = ProductImage::create([
            'product_id' => $productModel->id,
            'url' => Storage::disk('s3')->url($path),
            'alt_text' => $request->input('alt_text'),
            'sort_order' => $productModel->images()->count(),
        ]);

        return ApiResponse::success([
            'id' => $image->id,
            'url' => $image->url,
            'alt_text' => $image->alt_text,
            'sort_order' => $image->sort_order,
        ], 201);
    }

    public function update(UpdateProductImageRequest $request, string $product, string $image): JsonResponse
    {
        $imageModel = ProductImage::where('product_id', $product)->find($image);

        if (! $imageModel) {
            throw new ApiException('not_found', 'Image not found.', 404);
        }

        $imageModel->update(['alt_text' => $request->input('alt_text')]);

        return ApiResponse::success([
            'id' => $imageModel->id,
            'url' => $imageModel->url,
            'alt_text' => $imageModel->alt_text,
            'sort_order' => $imageModel->sort_order,
        ]);
    }

    public function reorder(ReorderProductImagesRequest $request, string $product): JsonResponse
    {
        $productModel = Product::find($product);

        if (! $productModel) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $imageIds = $request->input('image_ids');
        $existingIds = $productModel->images()->pluck('id')->all();

        if (count($imageIds) !== count($existingIds) || array_diff($imageIds, $existingIds) !== []) {
            throw ApiException::businessRule('invalid_image_set', 'The image list must contain exactly this product\'s images.');
        }

        DB::transaction(function () use ($imageIds) {
            foreach ($imageIds as $index => $imageId) {
                ProductImage::where('id', $imageId)->update(['sort_order' => $index]);
            }
        });

        $images = $productModel->images()->orderBy('sort_order')->get()->map(fn ($img) => [
            'id' => $img->id,
            'url' => $img->url,
            'alt_text' => $img->alt_text,
            'sort_order' => $img->sort_order,
        ]);

        return ApiResponse::success(['items' => $images]);
    }

    public function destroy(string $product, string $image): JsonResponse
    {
        $imageModel = ProductImage::where('product_id', $product)->find($image);

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
}

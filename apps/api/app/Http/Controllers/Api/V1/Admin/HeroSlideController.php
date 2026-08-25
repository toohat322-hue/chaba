<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderHeroSlidesRequest;
use App\Http\Requests\Admin\StoreHeroSlideImageRequest;
use App\Http\Requests\Admin\StoreHeroSlideRequest;
use App\Http\Requests\Admin\UpdateHeroSlideRequest;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeroSlideController extends Controller
{
    public function index(): JsonResponse
    {
        $slides = HeroSlide::with('product.images')->orderBy('sort_order')->get()->map(fn ($slide) => $this->present($slide));

        return ApiResponse::success($slides);
    }

    public function store(StoreHeroSlideRequest $request): JsonResponse
    {
        $slide = HeroSlide::create($request->validated() + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => HeroSlide::count(),
        ]);
        $slide->load('product.images');

        return ApiResponse::success($this->present($slide), 201);
    }

    public function update(UpdateHeroSlideRequest $request, string $heroSlide): JsonResponse
    {
        $slide = HeroSlide::find($heroSlide);

        if (! $slide) {
            throw new ApiException('not_found', 'Hero slide not found.', 404);
        }

        $slide->fill($request->validated())->save();
        $slide->load('product.images');

        return ApiResponse::success($this->present($slide));
    }

    public function destroy(string $heroSlide): JsonResponse
    {
        $slide = HeroSlide::find($heroSlide);

        if (! $slide) {
            throw new ApiException('not_found', 'Hero slide not found.', 404);
        }

        $this->deleteStoredImage($slide->getRawOriginal('image_url'));
        $this->deleteStoredImage($slide->getRawOriginal('mobile_image_url'));
        $slide->delete();

        return ApiResponse::success(['message' => 'Hero slide deleted.']);
    }

    public function uploadImage(StoreHeroSlideImageRequest $request, string $heroSlide): JsonResponse
    {
        return $this->storeImage($request, $heroSlide, 'image_url');
    }

    public function uploadMobileImage(StoreHeroSlideImageRequest $request, string $heroSlide): JsonResponse
    {
        return $this->storeImage($request, $heroSlide, 'mobile_image_url');
    }

    private function storeImage(StoreHeroSlideImageRequest $request, string $heroSlide, string $column): JsonResponse
    {
        $slide = HeroSlide::find($heroSlide);

        if (! $slide) {
            throw new ApiException('not_found', 'Hero slide not found.', 404);
        }

        // A slide has exactly one desktop and one mobile image (not a
        // gallery like ProductImage) — uploading again replaces the
        // existing one rather than adding a row.
        $this->deleteStoredImage($slide->getRawOriginal($column));

        $file = $request->file('image');
        $path = 'hero-slides/'.$slide->id.'/'.Str::uuid().'.'.$file->extension();
        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');

        $slide->update([$column => Storage::disk('s3')->url($path)]);
        $slide->load('product.images');

        return ApiResponse::success($this->present($slide));
    }

    public function reorder(ReorderHeroSlidesRequest $request): JsonResponse
    {
        $slideIds = $request->input('slide_ids');
        $existingIds = HeroSlide::pluck('id')->all();

        if (count($slideIds) !== count($existingIds) || array_diff($slideIds, $existingIds) !== []) {
            throw ApiException::businessRule('invalid_slide_set', 'The slide list must contain exactly the current hero slides.');
        }

        DB::transaction(function () use ($slideIds) {
            foreach ($slideIds as $index => $slideId) {
                HeroSlide::where('id', $slideId)->update(['sort_order' => $index]);
            }
        });

        $slides = HeroSlide::with('product.images')->orderBy('sort_order')->get()->map(fn ($slide) => $this->present($slide));

        return ApiResponse::success($slides);
    }

    private function deleteStoredImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        $key = MediaUrl::key($url);

        if ($key) {
            Storage::disk('s3')->delete($key);
        }
    }

    private function present(HeroSlide $slide): array
    {
        /** @var Product|null $product */
        $product = $slide->product;
        $productImage = $product?->images->sortBy('sort_order')->first();

        return [
            'id' => $slide->id,
            'product' => $product ? [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => ['ar' => $product->name_ar, 'fr' => $product->name_fr, 'en' => $product->name_en],
                'image_url' => $productImage?->url,
            ] : null,
            'image_url' => $slide->image_url,
            'mobile_image_url' => $slide->mobile_image_url,
            'title' => ['ar' => $slide->title_ar, 'fr' => $slide->title_fr, 'en' => $slide->title_en],
            'subtitle' => ['ar' => $slide->subtitle_ar, 'fr' => $slide->subtitle_fr, 'en' => $slide->subtitle_en],
            'cta_label' => ['ar' => $slide->cta_label_ar, 'fr' => $slide->cta_label_fr, 'en' => $slide->cta_label_en],
            'is_active' => $slide->is_active,
            'sort_order' => $slide->sort_order,
            'start_date' => $slide->start_date?->toIso8601String(),
            'end_date' => $slide->end_date?->toIso8601String(),
        ];
    }
}

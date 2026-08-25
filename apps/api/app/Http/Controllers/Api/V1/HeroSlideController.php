<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Support\ApiResponse;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

/**
 * Public, unauthenticated: the homepage hero carousel. Only active slides
 * whose product is itself active, within the optional start/end date
 * window, ordered by sort_order — everything the frontend needs in one
 * request (same "one aggregated response" shape as FooterController).
 */
class HeroSlideController extends Controller
{
    private const FALLBACK_CTA_LABEL = ['ar' => 'تسوّق الآن', 'fr' => 'Achetez maintenant', 'en' => 'Shop Now'];

    public function __invoke(): JsonResponse
    {
        // Fetched on every homepage load — worth caching like the rest of
        // the storefront's read-heavy public data. The one tradeoff: a
        // slide's start_date/end_date boundary can lag by up to the cache's
        // TTL instead of flipping the instant the clock crosses it — a
        // scheduled slide showing up to ~10 minutes early/late is an
        // acceptable price for not re-querying this on every single request.
        $slides = CatalogCache::remember('hero-slides', function () {
            return HeroSlide::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->whereHas('product', fn ($q) => $q->where('status', 'active'))
                ->with(['product' => fn ($q) => $q->with('images')])
                ->orderBy('sort_order')
                ->get();
        });

        return ApiResponse::success($slides->map(fn ($slide) => $this->present($slide)));
    }

    private function present(HeroSlide $slide): array
    {
        $product = $slide->product;
        $productImage = $product->images->sortBy('sort_order')->first();

        return [
            'id' => $slide->id,
            'product' => [
                'slug' => $product->slug,
                'name' => ['ar' => $product->name_ar, 'fr' => $product->name_fr, 'en' => $product->name_en],
            ],
            'image_url' => $slide->image_url ?? $productImage?->url,
            'mobile_image_url' => $slide->mobile_image_url,
            'title' => [
                'ar' => $slide->title_ar ?: $product->name_ar,
                'fr' => $slide->title_fr ?: $product->name_fr,
                'en' => $slide->title_en ?: $product->name_en,
            ],
            'subtitle' => [
                'ar' => $slide->subtitle_ar ?: $this->truncate($product->description_ar),
                'fr' => $slide->subtitle_fr ?: $this->truncate($product->description_fr),
                'en' => $slide->subtitle_en ?: $this->truncate($product->description_en),
            ],
            'cta_label' => [
                'ar' => $slide->cta_label_ar ?: self::FALLBACK_CTA_LABEL['ar'],
                'fr' => $slide->cta_label_fr ?: self::FALLBACK_CTA_LABEL['fr'],
                'en' => $slide->cta_label_en ?: self::FALLBACK_CTA_LABEL['en'],
            ],
        ];
    }

    private function truncate(?string $text, int $length = 140): ?string
    {
        if (! $text) {
            return null;
        }

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length).'…' : $text;
    }
}

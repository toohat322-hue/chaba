<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SlugRedirect;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resolves a retired product/category slug (see SlugRedirect and the
 * 2026_08_20_000003 migration) to the entity's current slug, so the
 * storefront can 301 an old URL instead of 404ing it. Only ever points at
 * an entity in its normal publicly-visible state — same rule the public
 * Product/Category "show" endpoints already enforce — so a redirect never
 * lands on an archived/inactive record.
 */
class RedirectController extends Controller
{
    public function resolve(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $slug = $request->string('slug')->toString();

        if (! in_array($type, ['product', 'category'], true) || $slug === '') {
            throw new ApiException('not_found', 'No redirect found.', 404);
        }

        $redirect = SlugRedirect::query()->where('type', $type)->where('old_slug', $slug)->first();

        if (! $redirect) {
            throw new ApiException('not_found', 'No redirect found.', 404);
        }

        $currentSlug = match ($type) {
            'product' => Product::query()->where('id', $redirect->entity_id)->where('status', 'active')->value('slug'),
            'category' => Category::query()->where('id', $redirect->entity_id)->where('is_active', true)->value('slug'),
        };

        if (! $currentSlug) {
            throw new ApiException('not_found', 'No redirect found.', 404);
        }

        return ApiResponse::success(['slug' => $currentSlug]);
    }
}

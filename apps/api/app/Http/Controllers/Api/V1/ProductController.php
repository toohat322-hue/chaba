<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductListResource;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', 'active')
            ->with(['images', 'variants.inventory']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('price_min')) {
            $query->where('base_price', '>=', (int) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('base_price', '<=', (int) $request->input('price_max'));
        }

        if ($request->filled('color')) {
            $query->whereHas('variants', fn ($q) => $q->where('color', $request->string('color')));
        }

        if ($request->filled('size')) {
            $query->whereHas('variants', fn ($q) => $q->where('size', $request->string('size')));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants.inventory', fn ($q) => $q->where('available_quantity', '>', 0));
        }

        match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            'rating' => $query->orderBy('avg_rating', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $cacheKey = 'products.index.'.md5(json_encode($request->query()));
        $paginator = CatalogCache::remember($cacheKey, fn () => $query->paginate(self::PER_PAGE, page: (int) $request->input('page', 1)));

        return ApiResponse::success([
            'items' => ProductListResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = CatalogCache::remember("products.show.{$slug}", function () use ($slug) {
            return Product::query()
                ->where('slug', $slug)
                ->where('status', 'active')
                ->with(['category', 'images', 'variants.inventory'])
                ->first();
        });

        if (! $product) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $related = CatalogCache::remember("products.related.{$product->id}", function () use ($product) {
            return Product::query()
                ->where('status', 'active')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->with(['images', 'variants.inventory'])
                ->orderByDesc('avg_rating')
                ->orderByDesc('review_count')
                ->limit(8)
                ->get();
        });
        $product->setRelation('related', $related);

        return ApiResponse::success(new ProductDetailResource($product));
    }

    public function featured(): JsonResponse
    {
        $products = CatalogCache::remember('products.featured', function () {
            return Product::query()
                ->where('status', 'active')
                ->where('featured', true)
                ->with(['images', 'variants.inventory'])
                ->orderBy('created_at', 'desc')
                ->limit(self::PER_PAGE)
                ->get();
        });

        return ApiResponse::success(ProductListResource::collection($products));
    }
}

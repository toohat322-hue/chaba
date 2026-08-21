<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    private const LIMIT = 24;

    /**
     * pg_trgm's default similarity_threshold (0.3, session-scoped via
     * set_limit()) is too strict for realistic short typos — e.g. "malki"
     * vs "Malaki" (one dropped letter) scores only 0.25 and would never
     * match. 0.2 catches that case with zero false positives against the
     * current catalog (verified manually); revisit if the catalog grows
     * and starts producing noisy matches at this threshold.
     */
    private const SIMILARITY_THRESHOLD = 0.2;

    /**
     * PRD §7.4/§14: typo-tolerant, multilingual (ar/fr/en) product search via
     * Postgres pg_trgm — `%` is the trigram similarity operator, ILIKE covers
     * short queries where there aren't enough trigrams to compare usefully.
     * PRD edge case #27: an empty/whitespace query returns a popular-products
     * fallback, not an empty error page; a query with zero matches does the
     * same, alongside a `no_results` flag the frontend can use to show the
     * "did you mean" / browse-categories fallback state.
     */
    public function index(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());

        if ($q === '') {
            return ApiResponse::success([
                'items' => ProductListResource::collection($this->popularProducts()),
                'query' => $q,
                'fallback' => true,
            ]);
        }

        DB::select('SELECT set_limit(?)', [self::SIMILARITY_THRESHOLD]);

        $like = '%'.$q.'%';

        $query = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($q, $like) {
                $query->whereRaw('name_ar % ?', [$q])
                    ->orWhereRaw('name_fr % ?', [$q])
                    ->orWhereRaw('name_en % ?', [$q])
                    ->orWhere('name_ar', 'ILIKE', $like)
                    ->orWhere('name_fr', 'ILIKE', $like)
                    ->orWhere('name_en', 'ILIKE', $like);
            })
            ->selectRaw('products.*, GREATEST(similarity(name_ar, ?), similarity(name_fr, ?), similarity(name_en, ?)) as relevance', [$q, $q, $q])
            ->with(['images', 'variants.inventory']);

        // Same filters as ProductController@index (category browsing) —
        // search results should be filterable the same way.
        if ($request->filled('price_min')) {
            $query->where('base_price', '>=', (int) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('base_price', '<=', (int) $request->input('price_max'));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants.inventory', fn ($q) => $q->where('available_quantity', '>', 0));
        }

        // Relevance is the default and whole point of search — only an
        // explicit, recognized `sort` overrides it (unlike category
        // browsing, which always sorts by something and defaults to
        // `newest`).
        if ($request->filled('sort')) {
            match ($request->string('sort')->toString()) {
                'price_asc' => $query->orderBy('base_price', 'asc'),
                'price_desc' => $query->orderBy('base_price', 'desc'),
                'rating' => $query->orderBy('avg_rating', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                default => $query->orderByDesc('relevance'),
            };
        } else {
            $query->orderByDesc('relevance');
        }

        $paginator = $query->paginate(self::LIMIT, page: (int) $request->input('page', 1));

        if ($paginator->total() === 0) {
            return ApiResponse::success([
                'items' => [],
                'query' => $q,
                'no_results' => true,
                'suggestions' => ProductListResource::collection($this->popularProducts()),
            ]);
        }

        return ApiResponse::success([
            'items' => ProductListResource::collection($paginator->items()),
            'query' => $q,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    private function popularProducts()
    {
        return Product::query()
            ->where('status', 'active')
            ->where('featured', true)
            ->with(['images', 'variants.inventory'])
            ->limit(12)
            ->get();
    }
}

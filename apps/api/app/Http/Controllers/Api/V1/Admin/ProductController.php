<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SlugRedirect;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\CsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['category', 'images', 'variants.inventory']);

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('name_ar', 'ILIKE', $like)
                    ->orWhere('name_fr', 'ILIKE', $like)
                    ->orWhere('name_en', 'ILIKE', $like);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->string('category'));
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => AdminProductResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // cursor() doesn't support eager loading (Laravel would N+1 the
        // category relation per row) — a plain id->name_en map is cheap to
        // preload once since a store has a handful of categories, not
        // thousands.
        $categoryNames = Category::pluck('name_en', 'id');

        $query = Product::query();

        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($like) {
                $q->where('name_ar', 'ILIKE', $like)
                    ->orWhere('name_fr', 'ILIKE', $like)
                    ->orWhere('name_en', 'ILIKE', $like);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->string('category'));
        }

        return CsvExport::stream(
            'products.csv',
            ['Name (EN)', 'Slug', 'Category', 'Status', 'Base Price (DZD)', 'Featured', 'Avg Rating', 'Review Count'],
            (function () use ($query, $categoryNames) {
                foreach ($query->orderByDesc('created_at')->cursor() as $product) {
                    yield [
                        $product->name_en,
                        $product->slug,
                        $categoryNames[$product->category_id] ?? null,
                        $product->status,
                        round($product->base_price / 100, 2),
                        $product->featured ? 'Yes' : 'No',
                        $product->avg_rating,
                        $product->review_count,
                    ];
                }
            })(),
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create([
                'category_id' => $request->input('category_id'),
                'name_ar' => $request->input('name_ar'),
                'name_fr' => $request->input('name_fr'),
                'name_en' => $request->input('name_en'),
                'slug' => $request->input('slug') ?: Str::slug($request->input('name_en')).'-'.Str::random(6),
                'description_ar' => $request->input('description_ar'),
                'description_fr' => $request->input('description_fr'),
                'description_en' => $request->input('description_en'),
                'base_price' => $request->input('base_price'),
                'compare_at_price' => $request->input('compare_at_price'),
                'status' => $request->input('status'),
                'featured' => $request->boolean('featured'),
                'seo_title' => $request->input('seo_title'),
                'seo_description' => $request->input('seo_description'),
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $request->input('sku'),
                'size_value' => $request->input('size_value'),
                'size_unit' => $request->input('size_unit'),
            ]);

            Inventory::create([
                'variant_id' => $variant->id,
                'stock_quantity' => $request->input('initial_stock'),
                'reserved_quantity' => 0,
                'low_stock_threshold' => $request->input('low_stock_threshold') ?? 5,
            ]);

            return $product;
        });

        AuditLogger::log($request->user(), 'product.created', $product);

        return ApiResponse::success(
            new AdminProductResource($product->load(['category', 'images', 'variants.inventory'])),
            201,
        );
    }

    public function show(string $product): JsonResponse
    {
        $model = Product::with(['category', 'images', 'variants.inventory'])->find($product);

        if (! $model) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        return ApiResponse::success(new AdminProductResource($model));
    }

    public function update(UpdateProductRequest $request, string $product): JsonResponse
    {
        $model = Product::find($product);

        if (! $model) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $model->fill($request->validated());
        $changes = AuditLogger::diff($model);
        $model->save();

        if (isset($changes['slug'])) {
            SlugRedirect::query()->updateOrCreate(
                ['type' => 'product', 'old_slug' => $changes['slug'][0]],
                ['entity_id' => $model->id],
            );
        }

        AuditLogger::log($request->user(), 'product.updated', $model, $changes);

        return ApiResponse::success(new AdminProductResource($model->load(['category', 'images', 'variants.inventory'])));
    }

    /**
     * Soft-delete: archives rather than hard-deletes, so order history
     * referencing this product (once Orders exists) is never orphaned.
     */
    public function destroy(Request $request, string $product): JsonResponse
    {
        $model = Product::find($product);

        if (! $model) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $model->update(['status' => 'archived']);

        AuditLogger::log($request->user(), 'product.archived', $model);

        return ApiResponse::success(['message' => 'Product archived.']);
    }
}

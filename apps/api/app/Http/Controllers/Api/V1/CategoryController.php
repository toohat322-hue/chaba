<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $tree = CatalogCache::remember('categories.tree', function () {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->with('children')])
                ->orderBy('sort_order')
                ->get();
        });

        return ApiResponse::success(CategoryResource::collection($tree));
    }

    public function show(string $slug): JsonResponse
    {
        $category = CatalogCache::remember("categories.show.{$slug}", function () use ($slug) {
            return Category::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->with('children')
                ->first();
        });

        if (! $category) {
            throw new ApiException('not_found', 'Category not found.', 404);
        }

        return ApiResponse::success(new CategoryResource($category));
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryImageRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\SlugRedirect;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();

        return ApiResponse::success($categories->map(fn ($category) => $this->serialize($category)));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'parent_id' => $request->input('parent_id'),
            'name_ar' => $request->input('name_ar'),
            'name_fr' => $request->input('name_fr'),
            'name_en' => $request->input('name_en'),
            'slug' => $request->input('slug') ?: Str::slug($request->input('name_en')).'-'.Str::random(6),
            'image_url' => $request->input('image_url'),
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
        ]);

        AuditLogger::log($request->user(), 'category.created', $category);

        return ApiResponse::success($this->serialize($category), 201);
    }

    public function update(UpdateCategoryRequest $request, string $category): JsonResponse
    {
        $model = Category::find($category);

        if (! $model) {
            throw new ApiException('not_found', 'Category not found.', 404);
        }

        $model->fill($request->validated());
        $changes = AuditLogger::diff($model);
        $model->save();

        if (isset($changes['slug'])) {
            SlugRedirect::query()->updateOrCreate(
                ['type' => 'category', 'old_slug' => $changes['slug'][0]],
                ['entity_id' => $model->id],
            );
        }

        AuditLogger::log($request->user(), 'category.updated', $model, $changes);

        return ApiResponse::success($this->serialize($model->loadCount('products')));
    }

    public function uploadImage(StoreCategoryImageRequest $request, string $category): JsonResponse
    {
        $model = Category::find($category);

        if (! $model) {
            throw new ApiException('not_found', 'Category not found.', 404);
        }

        $this->deleteStoredImage($model->getRawOriginal('image_url'));

        $file = $request->file('image');
        $path = 'categories/'.$model->id.'/'.Str::uuid().'.'.$file->extension();
        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');

        $model->update(['image_url' => Storage::disk('s3')->url($path)]);

        AuditLogger::log($request->user(), 'category.image_uploaded', $model);

        return ApiResponse::success($this->serialize($model->loadCount('products')));
    }

    public function deleteImage(Request $request, string $category): JsonResponse
    {
        $model = Category::find($category);

        if (! $model) {
            throw new ApiException('not_found', 'Category not found.', 404);
        }

        $this->deleteStoredImage($model->getRawOriginal('image_url'));
        $model->update(['image_url' => null]);

        AuditLogger::log($request->user(), 'category.image_deleted', $model);

        return ApiResponse::success($this->serialize($model->loadCount('products')));
    }

    private function deleteStoredImage(?string $url): void
    {
        $key = MediaUrl::key($url);

        if ($key) {
            Storage::disk('s3')->delete($key);
        }
    }

    /**
     * PRD edge case #24: deletion is blocked while the category has
     * products — admin must reassign them first, or archive (is_active
     * false) instead of hard-deleting.
     */
    public function destroy(Request $request, string $category): JsonResponse
    {
        $model = Category::withCount('products')->find($category);

        if (! $model) {
            throw new ApiException('not_found', 'Category not found.', 404);
        }

        if ($model->products_count > 0) {
            throw ApiException::conflict('category_has_products', 'Reassign or remove this category\'s products before deleting it.');
        }

        AuditLogger::log($request->user(), 'category.deleted', $model);

        $model->delete();

        return ApiResponse::success(['message' => 'Category deleted.']);
    }

    private function serialize(Category $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'slug' => $category->slug,
            'name' => ['ar' => $category->name_ar, 'fr' => $category->name_fr, 'en' => $category->name_en],
            'image_url' => $category->image_url,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'seo_title' => $category->seo_title,
            'seo_description' => $category->seo_description,
            'product_count' => $category->products_count ?? 0,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFooterFeatureRequest;
use App\Http\Requests\Admin\UpdateFooterFeatureRequest;
use App\Models\FooterFeature;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FooterFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FooterFeature::orderBy('sort_order')->get()->map(fn ($item) => $this->present($item));

        return ApiResponse::success($items);
    }

    public function store(StoreFooterFeatureRequest $request): JsonResponse
    {
        $feature = FooterFeature::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return ApiResponse::success($this->present($feature), 201);
    }

    public function update(UpdateFooterFeatureRequest $request, string $footerFeature): JsonResponse
    {
        $feature = FooterFeature::find($footerFeature);

        if (! $feature) {
            throw new ApiException('not_found', 'Footer feature not found.', 404);
        }

        $feature->fill($request->validated())->save();

        return ApiResponse::success($this->present($feature));
    }

    public function destroy(string $footerFeature): JsonResponse
    {
        $feature = FooterFeature::find($footerFeature);

        if (! $feature) {
            throw new ApiException('not_found', 'Footer feature not found.', 404);
        }

        $feature->delete();

        return ApiResponse::success(['message' => 'Footer feature deleted.']);
    }

    private function present(FooterFeature $feature): array
    {
        return [
            'id' => $feature->id,
            'icon' => $feature->icon,
            'title' => ['ar' => $feature->title_ar, 'fr' => $feature->title_fr, 'en' => $feature->title_en],
            'description' => [
                'ar' => $feature->description_ar,
                'fr' => $feature->description_fr,
                'en' => $feature->description_en,
            ],
            'is_active' => $feature->is_active,
            'sort_order' => $feature->sort_order,
        ];
    }
}

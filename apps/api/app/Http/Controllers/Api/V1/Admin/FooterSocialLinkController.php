<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFooterSocialLinkRequest;
use App\Http\Requests\Admin\UpdateFooterSocialLinkRequest;
use App\Models\FooterSocialLink;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FooterSocialLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FooterSocialLink::orderBy('sort_order')->get()->map(fn ($item) => $this->present($item));

        return ApiResponse::success($items);
    }

    public function store(StoreFooterSocialLinkRequest $request): JsonResponse
    {
        $link = FooterSocialLink::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return ApiResponse::success($this->present($link), 201);
    }

    public function update(UpdateFooterSocialLinkRequest $request, string $footerSocialLink): JsonResponse
    {
        $link = FooterSocialLink::find($footerSocialLink);

        if (! $link) {
            throw new ApiException('not_found', 'Social link not found.', 404);
        }

        $link->fill($request->validated())->save();

        return ApiResponse::success($this->present($link));
    }

    public function destroy(string $footerSocialLink): JsonResponse
    {
        $link = FooterSocialLink::find($footerSocialLink);

        if (! $link) {
            throw new ApiException('not_found', 'Social link not found.', 404);
        }

        $link->delete();

        return ApiResponse::success(['message' => 'Social link deleted.']);
    }

    private function present(FooterSocialLink $link): array
    {
        return [
            'id' => $link->id,
            'platform' => $link->platform,
            'url' => $link->url,
            'is_active' => $link->is_active,
            'sort_order' => $link->sort_order,
        ];
    }
}

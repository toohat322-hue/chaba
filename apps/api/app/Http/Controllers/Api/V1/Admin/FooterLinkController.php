<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFooterLinkRequest;
use App\Http\Requests\Admin\UpdateFooterLinkRequest;
use App\Models\FooterColumn;
use App\Models\FooterLink;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FooterLinkController extends Controller
{
    public function store(StoreFooterLinkRequest $request, string $footerColumn): JsonResponse
    {
        $column = FooterColumn::find($footerColumn);

        if (! $column) {
            throw new ApiException('not_found', 'Footer column not found.', 404);
        }

        $link = $column->links()->create(
            $request->validated() + ['is_active' => $request->boolean('is_active', true)]
        );

        return ApiResponse::success($this->present($link), 201);
    }

    public function update(UpdateFooterLinkRequest $request, string $footerLink): JsonResponse
    {
        $link = FooterLink::find($footerLink);

        if (! $link) {
            throw new ApiException('not_found', 'Footer link not found.', 404);
        }

        $link->fill($request->validated())->save();

        return ApiResponse::success($this->present($link));
    }

    public function destroy(string $footerLink): JsonResponse
    {
        $link = FooterLink::find($footerLink);

        if (! $link) {
            throw new ApiException('not_found', 'Footer link not found.', 404);
        }

        $link->delete();

        return ApiResponse::success(['message' => 'Footer link deleted.']);
    }

    private function present(FooterLink $link): array
    {
        return [
            'id' => $link->id,
            'footer_column_id' => $link->footer_column_id,
            'label' => ['ar' => $link->label_ar, 'fr' => $link->label_fr, 'en' => $link->label_en],
            'url' => $link->url,
            'is_active' => $link->is_active,
            'sort_order' => $link->sort_order,
        ];
    }
}

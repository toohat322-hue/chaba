<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFooterColumnRequest;
use App\Http\Requests\Admin\UpdateFooterColumnRequest;
use App\Models\FooterColumn;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FooterColumnController extends Controller
{
    public function index(): JsonResponse
    {
        $columns = FooterColumn::with('links')->orderBy('sort_order')->get()->map(fn ($column) => $this->present($column));

        return ApiResponse::success($columns);
    }

    public function store(StoreFooterColumnRequest $request): JsonResponse
    {
        $column = FooterColumn::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return ApiResponse::success($this->present($column->load('links')), 201);
    }

    public function update(UpdateFooterColumnRequest $request, string $footerColumn): JsonResponse
    {
        $column = FooterColumn::find($footerColumn);

        if (! $column) {
            throw new ApiException('not_found', 'Footer column not found.', 404);
        }

        $column->fill($request->validated())->save();

        return ApiResponse::success($this->present($column->load('links')));
    }

    /**
     * Cascade-deletes the column's links too (footer_links.footer_column_id
     * has cascadeOnDelete at the DB level) — no separate confirmation step
     * beyond the frontend's own confirm() dialog, same as every other
     * admin delete in this app.
     */
    public function destroy(string $footerColumn): JsonResponse
    {
        $column = FooterColumn::find($footerColumn);

        if (! $column) {
            throw new ApiException('not_found', 'Footer column not found.', 404);
        }

        $column->delete();

        return ApiResponse::success(['message' => 'Footer column deleted.']);
    }

    private function present(FooterColumn $column): array
    {
        return [
            'id' => $column->id,
            'title' => ['ar' => $column->title_ar, 'fr' => $column->title_fr, 'en' => $column->title_en],
            'is_active' => $column->is_active,
            'sort_order' => $column->sort_order,
            'links' => $column->links->map(fn ($link) => [
                'id' => $link->id,
                'label' => ['ar' => $link->label_ar, 'fr' => $link->label_fr, 'en' => $link->label_en],
                'url' => $link->url,
                'is_active' => $link->is_active,
                'sort_order' => $link->sort_order,
            ]),
        ];
    }
}

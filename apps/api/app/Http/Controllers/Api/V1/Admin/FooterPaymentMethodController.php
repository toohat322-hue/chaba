<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFooterPaymentMethodRequest;
use App\Http\Requests\Admin\UpdateFooterPaymentMethodRequest;
use App\Models\FooterPaymentMethod;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FooterPaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FooterPaymentMethod::orderBy('sort_order')->get()->map(fn ($item) => $this->present($item));

        return ApiResponse::success($items);
    }

    public function store(StoreFooterPaymentMethodRequest $request): JsonResponse
    {
        $method = FooterPaymentMethod::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return ApiResponse::success($this->present($method), 201);
    }

    public function update(UpdateFooterPaymentMethodRequest $request, string $footerPaymentMethod): JsonResponse
    {
        $method = FooterPaymentMethod::find($footerPaymentMethod);

        if (! $method) {
            throw new ApiException('not_found', 'Payment method not found.', 404);
        }

        $method->fill($request->validated())->save();

        return ApiResponse::success($this->present($method));
    }

    public function destroy(string $footerPaymentMethod): JsonResponse
    {
        $method = FooterPaymentMethod::find($footerPaymentMethod);

        if (! $method) {
            throw new ApiException('not_found', 'Payment method not found.', 404);
        }

        $method->delete();

        return ApiResponse::success(['message' => 'Payment method deleted.']);
    }

    private function present(FooterPaymentMethod $method): array
    {
        return [
            'id' => $method->id,
            'name' => ['ar' => $method->name_ar, 'fr' => $method->name_fr, 'en' => $method->name_en],
            'icon' => $method->icon,
            'is_active' => $method->is_active,
            'sort_order' => $method->sort_order,
        ];
    }
}

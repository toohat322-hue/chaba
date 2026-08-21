<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, string $product): JsonResponse
    {
        $productModel = Product::find($product);

        if (! $productModel) {
            throw new ApiException('not_found', 'Product not found.', 404);
        }

        $this->assertNoDuplicateSize($productModel->id, $request->input('size_value'), $request->input('size_unit'));

        $createdVariant = DB::transaction(function () use ($request, $productModel) {
            $variant = ProductVariant::create([
                'product_id' => $productModel->id,
                'sku' => $request->input('sku'),
                'color' => $request->input('color'),
                'size' => $request->input('size'),
                'price_override' => $request->input('price_override'),
                'compare_at_price' => $request->input('compare_at_price'),
                'size_value' => $request->input('size_value'),
                'size_unit' => $request->input('size_unit'),
                'sort_order' => $request->input('sort_order', 0),
                'is_active' => $request->boolean('is_active', true),
                'weight' => $request->input('weight'),
            ]);

            Inventory::create([
                'variant_id' => $variant->id,
                'stock_quantity' => $request->input('initial_stock'),
                'reserved_quantity' => 0,
                'low_stock_threshold' => $request->input('low_stock_threshold') ?? 5,
            ]);

            return $variant;
        });

        AuditLogger::log($request->user(), 'variant.created', $createdVariant);

        return ApiResponse::success(
            new AdminProductResource($productModel->load(['category', 'images', 'variants.inventory'])),
            201,
        );
    }

    public function update(UpdateProductVariantRequest $request, string $product, string $variant): JsonResponse
    {
        $variantModel = ProductVariant::where('product_id', $product)->find($variant);

        if (! $variantModel) {
            throw new ApiException('not_found', 'Variant not found.', 404);
        }

        $this->assertNoDuplicateSize(
            $product,
            $request->input('size_value', $variantModel->size_value),
            $request->input('size_unit', $variantModel->size_unit),
            excludeVariantId: $variantModel->id,
        );

        $changes = DB::transaction(function () use ($request, $variantModel) {
            $variantModel->fill($request->safe()->except(['low_stock_threshold']));
            $changes = AuditLogger::diff($variantModel);
            $variantModel->save();

            if ($request->filled('low_stock_threshold')) {
                $variantModel->inventory?->update(['low_stock_threshold' => $request->input('low_stock_threshold')]);
            }

            return $changes;
        });

        AuditLogger::log($request->user(), 'variant.updated', $variantModel, $changes);

        $productModel = Product::with(['category', 'images', 'variants.inventory'])->find($product);

        return ApiResponse::success(new AdminProductResource($productModel));
    }

    /**
     * Backed by the product_variants_product_size_unique DB index — checked
     * here first so a duplicate size produces a clean business-rule error
     * (matching last_variant/variant_has_orders below) instead of a raw
     * constraint-violation exception.
     */
    private function assertNoDuplicateSize(string $productId, ?float $sizeValue, ?string $sizeUnit, ?string $excludeVariantId = null): void
    {
        if ($sizeValue === null || ! $sizeUnit) {
            return;
        }

        $exists = ProductVariant::where('product_id', $productId)
            ->where('size_value', $sizeValue)
            ->where('size_unit', $sizeUnit)
            ->when($excludeVariantId, fn ($q) => $q->where('id', '!=', $excludeVariantId))
            ->exists();

        if ($exists) {
            throw ApiException::conflict('duplicate_variant_size', 'This product already has a variant with this size.');
        }
    }

    public function destroy(Request $request, string $product, string $variant): JsonResponse
    {
        $variantModel = ProductVariant::where('product_id', $product)->find($variant);

        if (! $variantModel) {
            throw new ApiException('not_found', 'Variant not found.', 404);
        }

        if (ProductVariant::where('product_id', $product)->count() <= 1) {
            throw ApiException::businessRule('last_variant', 'A product must have at least one variant — add another variant before deleting this one.');
        }

        if (OrderItem::where('variant_id', $variantModel->id)->exists()) {
            throw ApiException::conflict('variant_has_orders', 'This variant has existing orders and cannot be deleted.');
        }

        AuditLogger::log($request->user(), 'variant.deleted', $variantModel);

        $variantModel->delete();

        $productModel = Product::with(['category', 'images', 'variants.inventory'])->find($product);

        return ApiResponse::success(new AdminProductResource($productModel));
    }
}

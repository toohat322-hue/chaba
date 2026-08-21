<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items->map(function ($item) {
            $variant = $item->variant;
            $product = $variant->product;
            $currentPrice = $variant->effectivePrice();
            $primaryImage = $product->images->sortBy('sort_order')->first();

            return [
                'id' => $item->id,
                'variant_id' => $variant->id,
                'product_slug' => $product->slug,
                'product_name' => [
                    'ar' => $product->name_ar,
                    'fr' => $product->name_fr,
                    'en' => $product->name_en,
                ],
                'sku' => $variant->sku,
                'color' => $variant->color,
                'size' => $variant->size,
                'size_value' => $variant->size_value,
                'size_unit' => $variant->size_unit,
                'quantity' => $item->quantity,
                'price_snapshot' => $item->price_snapshot,
                'current_price' => $currentPrice,
                'price_changed' => $item->price_snapshot !== $currentPrice,
                'image_url' => $primaryImage?->url,
                'available_quantity' => $variant->inventory?->available_quantity ?? 0,
                'exceeds_stock' => $item->quantity > ($variant->inventory?->available_quantity ?? 0),
            ];
        });

        $subtotal = $items->sum(fn ($item) => $item['current_price'] * $item['quantity']);

        $coupon = $this->coupon_code ? Coupon::where('code', $this->coupon_code)->first() : null;
        $couponValid = $coupon && $coupon->isCurrentlyValidFor($subtotal);

        return [
            'id' => $this->id,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $couponValid ? app(CouponService::class)->calculateDiscount($coupon, $subtotal) : 0,
            'coupon' => $couponValid ? ['code' => $coupon->code, 'type' => $coupon->type] : null,
            'item_count' => $items->sum('quantity'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wishlist */
class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->variant->product;
        $primaryImage = $product->images->sortBy('sort_order')->first();
        $inStock = $product->variants->contains(
            fn ($variant) => $variant->inventory && $variant->inventory->available_quantity > 0
        );

        return [
            'id' => $this->id,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'variant_id' => $this->variant_id,
            'name' => [
                'ar' => $product->name_ar,
                'fr' => $product->name_fr,
                'en' => $product->name_en,
            ],
            'price' => $product->startingPrice(),
            'compare_at_price' => $product->compare_at_price,
            'in_stock' => $inStock,
            'image_url' => $primaryImage?->url,
            'created_at' => $this->created_at,
        ];
    }
}

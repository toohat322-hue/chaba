<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImage = $this->images->sortBy('sort_order')->first();
        // A disabled (is_active=false) size never counts toward "in stock" —
        // it's not something a customer can actually buy.
        $inStock = $this->variants->where('is_active', true)->contains(
            fn ($variant) => $variant->inventory && $variant->inventory->available_quantity > 0
        );

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => [
                'ar' => $this->name_ar,
                'fr' => $this->name_fr,
                'en' => $this->name_en,
            ],
            'price' => $this->startingPrice(),
            'compare_at_price' => $this->compare_at_price,
            'in_stock' => $inStock,
            'featured' => $this->featured,
            'avg_rating' => $this->avg_rating,
            'review_count' => $this->review_count,
            'image_url' => $primaryImage?->url,
            'image_alt' => $primaryImage?->alt_text,
            'updated_at' => $this->updated_at,
        ];
    }
}

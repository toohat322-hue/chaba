<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => [
                'ar' => $this->name_ar,
                'fr' => $this->name_fr,
                'en' => $this->name_en,
            ],
            'description' => [
                'ar' => $this->description_ar,
                'fr' => $this->description_fr,
                'en' => $this->description_en,
            ],
            'base_price' => $this->base_price,
            'compare_at_price' => $this->compare_at_price,
            'status' => $this->status,
            'featured' => $this->featured,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'updated_at' => $this->updated_at,
            'avg_rating' => $this->avg_rating,
            'review_count' => $this->review_count,
            'category' => $this->whenLoaded('category', fn () => [
                'slug' => $this->category->slug,
                'name' => [
                    'ar' => $this->category->name_ar,
                    'fr' => $this->category->name_fr,
                    'en' => $this->category->name_en,
                ],
            ]),
            'images' => $this->images->sortBy('sort_order')->values()->map(fn ($image) => [
                'url' => $image->url,
                'alt_text' => $image->alt_text,
                'variant_id' => $image->variant_id,
            ]),
            'variants' => $this->variants->where('is_active', true)->sortBy('sort_order')->values()->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'color' => $variant->color,
                'size' => $variant->size,
                'size_value' => $variant->size_value,
                'size_unit' => $variant->size_unit,
                'price' => $variant->effectivePrice(),
                'compare_at_price' => $variant->effectiveComparePrice(),
                'sort_order' => $variant->sort_order,
                'available_quantity' => $variant->inventory?->available_quantity ?? 0,
                'low_stock' => $variant->inventory
                    && $variant->inventory->available_quantity > 0
                    && $variant->inventory->available_quantity <= $variant->inventory->low_stock_threshold,
            ]),
            'related' => ProductListResource::collection($this->whenLoaded('related')),
        ];
    }
}

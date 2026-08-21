<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => [
                    'ar' => $this->category->name_ar,
                    'fr' => $this->category->name_fr,
                    'en' => $this->category->name_en,
                ],
            ]),
            'name' => ['ar' => $this->name_ar, 'fr' => $this->name_fr, 'en' => $this->name_en],
            'description' => ['ar' => $this->description_ar, 'fr' => $this->description_fr, 'en' => $this->description_en],
            'base_price' => $this->base_price,
            'compare_at_price' => $this->compare_at_price,
            'status' => $this->status,
            'featured' => $this->featured,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'avg_rating' => $this->avg_rating,
            'review_count' => $this->review_count,
            'images' => $this->whenLoaded('images', fn () => $this->images->sortBy('sort_order')->values()->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->sortBy('sort_order')->values()->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'color' => $variant->color,
                'size' => $variant->size,
                'price_override' => $variant->price_override,
                'compare_at_price' => $variant->compare_at_price,
                'size_value' => $variant->size_value,
                'size_unit' => $variant->size_unit,
                'sort_order' => $variant->sort_order,
                'is_active' => $variant->is_active,
                'weight' => $variant->weight,
                'stock_quantity' => $variant->inventory?->stock_quantity ?? 0,
                'reserved_quantity' => $variant->inventory?->reserved_quantity ?? 0,
                'available_quantity' => $variant->inventory?->available_quantity ?? 0,
                'low_stock_threshold' => $variant->inventory?->low_stock_threshold ?? 0,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

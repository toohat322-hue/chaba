<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
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
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'updated_at' => $this->updated_at,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

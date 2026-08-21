<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'is_verified_purchase' => $this->is_verified_purchase,
            'status' => $this->status,
            'reviewer_name' => $this->whenLoaded('user', fn () => $this->user->full_name),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])),
            'report_count' => $this->whenCounted('reports'),
            'created_at' => $this->created_at,
        ];
    }
}

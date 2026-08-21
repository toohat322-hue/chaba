<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'category_id', 'name_ar', 'name_fr', 'name_en', 'slug',
        'description_ar', 'description_fr', 'description_en',
        'base_price', 'compare_at_price', 'status', 'seo_title',
        'seo_description', 'featured', 'avg_rating', 'review_count',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'base_price' => 'integer',
            'compare_at_price' => 'integer',
            'avg_rating' => 'float',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Lowest currently-effective price across active variants (price_override
     * if set, else the product's base_price) — what a "from X DA" listing
     * price should show when variants have different prices. A disabled
     * (is_active=false) size never affects this, same as it's excluded from
     * the public variants list entirely.
     */
    public function startingPrice(): int
    {
        $lowestOverride = $this->activeVariants()->pluck('price_override')->filter()->min();

        return $lowestOverride !== null ? min($lowestOverride, $this->base_price) : $this->base_price;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function activeVariants(): Collection
    {
        return $this->variants->where('is_active', true);
    }
}

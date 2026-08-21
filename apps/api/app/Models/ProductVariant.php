<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'sku', 'color', 'size', 'price_override', 'weight',
        'compare_at_price', 'size_value', 'size_unit', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_override' => 'integer',
            'weight' => 'float',
            'compare_at_price' => 'integer',
            'size_value' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function effectivePrice(): int
    {
        return $this->price_override ?? $this->product->base_price;
    }

    public function effectiveComparePrice(): ?int
    {
        return $this->compare_at_price ?? $this->product->compare_at_price;
    }

    public function formattedSize(): ?string
    {
        if ($this->size_value === null || ! $this->size_unit) {
            return null;
        }

        // "10" not "10.00" for whole numbers, matching how an admin would type it.
        $value = rtrim(rtrim(number_format($this->size_value, 2, '.', ''), '0'), '.');

        return "{$value} {$this->size_unit}";
    }
}

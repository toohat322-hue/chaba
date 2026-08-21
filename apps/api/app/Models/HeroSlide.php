<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroSlide extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'image_url', 'mobile_image_url',
        'title_ar', 'title_fr', 'title_en',
        'subtitle_ar', 'subtitle_fr', 'subtitle_en',
        'cta_label_ar', 'cta_label_fr', 'cta_label_en',
        'is_active', 'sort_order', 'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isCurrentlyVisible(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && now()->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'user_id', 'order_item_id', 'rating', 'title', 'body',
        'is_verified_purchase', 'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }
}

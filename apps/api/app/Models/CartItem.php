<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['cart_id', 'variant_id', 'quantity', 'price_snapshot'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_snapshot' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}

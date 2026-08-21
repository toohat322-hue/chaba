<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasUuids;

    // PRD §12 names this table `inventory` (singular) — override Eloquent's
    // default `inventories` auto-pluralization guess.
    protected $table = 'inventory';

    public $timestamps = false;

    protected $fillable = ['variant_id', 'stock_quantity', 'reserved_quantity', 'low_stock_threshold'];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            // available_quantity is a Postgres STORED generated column
            // (stock_quantity - reserved_quantity) — read-only here, never
            // written to directly, so oversell checks are always consistent.
            'available_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_value', 'start_date', 'end_date',
        'usage_limit_total', 'usage_limit_per_customer', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order_value' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'usage_limit_total' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Cheap attribute-only checks (active/date window/min order), used for
     * read-time display only — never the authoritative check before a
     * mutation. Usage limits require extra queries and are only re-checked
     * by CouponService::findValid() at apply/checkout time.
     */
    public function isCurrentlyValidFor(int $subtotal): bool
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

        if ($this->min_order_value && $subtotal < $this->min_order_value) {
            return false;
        }

        return true;
    }
}

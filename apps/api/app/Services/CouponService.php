<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Coupon;
use App\Models\User;

/**
 * Single source of truth for coupon eligibility, called both as a live
 * preview (cart apply, no lock) and as the authoritative check inside
 * OrderService::checkout()'s transaction (locked, to close the same class
 * of race CartService/OrderService already guard against for stock).
 */
class CouponService
{
    public function findValid(string $code, int $subtotal, ?User $user, bool $lock = false): Coupon
    {
        // Codes are matched case-insensitively — customers shouldn't have to
        // match the exact casing a marketing coupon happened to be created with.
        $query = Coupon::whereRaw('upper(code) = ?', [mb_strtoupper($code)]);

        if ($lock) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();

        if (! $coupon) {
            throw ApiException::businessRule('coupon_not_found', 'This coupon code does not exist.');
        }

        if (! $coupon->is_active) {
            throw ApiException::businessRule('coupon_inactive', 'This coupon is no longer active.');
        }

        if ($coupon->start_date && now()->lt($coupon->start_date)) {
            throw ApiException::businessRule('coupon_not_started', 'This coupon is not active yet.');
        }

        if ($coupon->end_date && now()->gt($coupon->end_date)) {
            throw ApiException::businessRule('coupon_expired', 'This coupon has expired.');
        }

        if ($coupon->min_order_value && $subtotal < $coupon->min_order_value) {
            throw ApiException::businessRule('coupon_min_order_not_met', 'Your order does not meet this coupon\'s minimum value.');
        }

        if ($coupon->type === 'bxgy') {
            throw ApiException::businessRule('coupon_type_not_supported', 'This coupon type is not supported yet.');
        }

        if ($coupon->usage_limit_total !== null && $coupon->usages()->count() >= $coupon->usage_limit_total) {
            throw ApiException::conflict('coupon_usage_limit_reached', 'This coupon has reached its usage limit.');
        }

        if ($coupon->usage_limit_per_customer !== null && $user) {
            $usedByCustomer = $coupon->usages()->where('user_id', $user->id)->count();

            if ($usedByCustomer >= $coupon->usage_limit_per_customer) {
                throw ApiException::conflict('coupon_customer_limit_reached', 'You have already used this coupon the maximum number of times.');
            }
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, int $subtotal): int
    {
        return match ($coupon->type) {
            'percentage' => min($subtotal, intdiv($subtotal * $coupon->value, 100)),
            'fixed' => min($coupon->value, $subtotal),
            default => 0,
        };
    }
}

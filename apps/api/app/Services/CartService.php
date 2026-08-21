<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PRD §20: adding to cart reserves stock for 30 minutes (guest carts).
 * Reservation is real (inventory.reserved_quantity), not just a validation
 * check — `available_quantity` is a Postgres generated column
 * (stock_quantity - reserved_quantity, from Phase 1), so every read of it
 * is already consistent; this service only ever adjusts reserved_quantity,
 * inside a transaction with lockForUpdate() on the inventory row wherever a
 * "read the current available amount, then decide" step happens (PRD edge
 * case #15 — two customers racing for the last unit).
 */
class CartService
{
    private const GUEST_EXPIRY_MINUTES = 30;

    public function resolveCart(Request $request): Cart
    {
        $user = $request->user('sanctum');

        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
        }

        $sessionToken = $request->header('X-Guest-Session');

        if (! $sessionToken) {
            throw new ApiException('guest_session_required', 'A guest session token is required.', 400);
        }

        $cart = Cart::firstOrCreate([
            'session_token' => $sessionToken,
            'user_id' => null,
            'status' => 'active',
        ]);

        $this->releaseExpiredGuestItems($cart);

        return $cart;
    }

    public function releaseExpiredGuestItems(Cart $cart): void
    {
        if ($cart->user_id !== null) {
            return;
        }

        foreach ($cart->items()->where('created_at', '<', now()->subMinutes(self::GUEST_EXPIRY_MINUTES))->get() as $item) {
            $this->removeItem($item);
        }
    }

    public function addItem(Cart $cart, string $variantId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity) {
            $variant = ProductVariant::find($variantId);

            if (! $variant) {
                throw new ApiException('not_found', 'Product variant not found.', 404);
            }

            $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();

            if (! $inventory || $inventory->available_quantity < $quantity) {
                throw ApiException::conflict('insufficient_stock', 'Not enough stock available.');
            }

            $inventory->increment('reserved_quantity', $quantity);

            $existing = CartItem::where('cart_id', $cart->id)->where('variant_id', $variantId)->first();

            if ($existing) {
                $existing->increment('quantity', $quantity);

                return $existing->fresh();
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price_snapshot' => $variant->effectivePrice(),
            ]);
        });
    }

    /**
     * @return CartItem|null Null if the update reduced quantity to zero (item removed).
     */
    public function updateItem(CartItem $item, int $quantity): ?CartItem
    {
        return DB::transaction(function () use ($item, $quantity) {
            $inventory = Inventory::where('variant_id', $item->variant_id)->lockForUpdate()->first();
            $delta = $quantity - $item->quantity;

            if ($delta > 0 && (! $inventory || $inventory->available_quantity < $delta)) {
                throw ApiException::conflict('insufficient_stock', 'Not enough stock available.');
            }

            if ($inventory && $delta !== 0) {
                $inventory->increment('reserved_quantity', $delta);
            }

            if ($quantity <= 0) {
                $item->delete();

                return null;
            }

            $item->update(['quantity' => $quantity]);

            return $item;
        });
    }

    public function removeItem(CartItem $item): void
    {
        DB::transaction(function () use ($item) {
            // Plain decrement, no lockForUpdate needed: this is an
            // unconditional write with no "read current value, then decide"
            // step, and a single UPDATE is already atomic against
            // concurrent writers.
            Inventory::where('variant_id', $item->variant_id)->decrement('reserved_quantity', $item->quantity);
            $item->delete();
        });
    }

    /**
     * Folds a guest cart's items into a just-authenticated user's own cart
     * (login/register/social-login all converge on this) — a pre-existing
     * gap for every login method, not just social login: guest carts are
     * keyed by session token, user carts by user_id, so without this a
     * guest's items were simply left behind the moment cart state was next
     * fetched under the new identity.
     *
     * Reuses removeItem()/addItem() rather than hand-rolling new locking —
     * each guest item's reservation is released, then re-reserved under the
     * user's cart, so the same stock-safety checks addItem() already
     * enforces apply here too. If stock no longer covers a given item (sold
     * out between add-to-cart and login), that one item is silently dropped
     * rather than failing the whole merge — a login must never hard-fail on
     * a merge problem.
     */
    public function mergeGuestCart(Cart $userCart, string $guestSessionToken): void
    {
        $guestCart = Cart::where('session_token', $guestSessionToken)
            ->whereNull('user_id')
            ->where('status', 'active')
            ->first();

        if (! $guestCart || $guestCart->id === $userCart->id) {
            return;
        }

        DB::transaction(function () use ($userCart, $guestCart) {
            foreach ($guestCart->items()->get() as $guestItem) {
                $variantId = $guestItem->variant_id;
                $quantity = $guestItem->quantity;

                $this->removeItem($guestItem);

                try {
                    $this->addItem($userCart, $variantId, $quantity);
                } catch (ApiException) {
                    // Insufficient stock to carry this item over — already
                    // released above, simply not re-added.
                }
            }

            $guestCart->update(['status' => 'converted']);
        });
    }
}

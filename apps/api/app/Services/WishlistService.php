<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * PRD §12/§25 Phase 3: wishlist is stored per-variant (matches the PRD's DB
 * design), but ProductListResource never exposes a variant_id — the
 * storefront's only wishlist entry points (product cards, the wishlist page)
 * have nothing but a product id available. addProduct()/removeProduct()
 * bridge that by resolving to the product's earliest-created variant, the
 * same "primary variant" a single-variant product naturally has.
 *
 * Guest/auth identity resolution mirrors CartService::resolveCart() exactly
 * (X-Guest-Session header vs Sanctum user), except a wishlist has no single
 * row to fetch-or-create — each item is scoped by the same identity pair
 * inline.
 */
class WishlistService
{
    public function resolveItems(Request $request): Collection
    {
        return Wishlist::where($this->resolveIdentity($request))
            ->with(['variant.product.images', 'variant.product.variants.inventory'])
            ->latest('created_at')
            ->get();
    }

    public function addProduct(Request $request, Product $product): Wishlist
    {
        $variant = $product->variants()->oldest('id')->first();

        if (! $variant) {
            throw ApiException::businessRule('no_variant_available', 'This product has no purchasable variant.');
        }

        $item = Wishlist::firstOrCreate([
            ...$this->resolveIdentity($request),
            'variant_id' => $variant->id,
        ]);

        // $timestamps = false means Eloquent never populates created_at in
        // memory itself — it's a DB-level useCurrent() default, so a
        // freshly-*created* row's in-memory instance has created_at = null
        // until re-fetched (an already-existing row from the `first` branch
        // of firstOrCreate is unaffected, but refreshing unconditionally is
        // one cheap, always-correct path instead of two).
        return $item->refresh();
    }

    public function removeProduct(Request $request, Product $product): void
    {
        $variantIds = $product->variants()->pluck('id');

        Wishlist::where($this->resolveIdentity($request))
            ->whereIn('variant_id', $variantIds)
            ->delete();
    }

    public function clear(Request $request): void
    {
        Wishlist::where($this->resolveIdentity($request))->delete();
    }

    /**
     * @return array{user_id: ?string, session_token: ?string}
     */
    private function resolveIdentity(Request $request): array
    {
        $user = $request->user('sanctum');

        if ($user) {
            return ['user_id' => $user->id, 'session_token' => null];
        }

        $sessionToken = $request->header('X-Guest-Session');

        if (! $sessionToken) {
            throw new ApiException('guest_session_required', 'A guest session token is required.', 400);
        }

        return ['user_id' => null, 'session_token' => $sessionToken];
    }
}

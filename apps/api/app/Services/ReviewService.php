<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PRD §7.12: verified-purchase detection, auto-publish for verified
 * purchases vs. a moderation queue for everyone else, and average rating
 * recalculated on every new/edited/removed/moderated review. Mirrors
 * OrderService's transaction discipline.
 */
class ReviewService
{
    public function submit(Product $product, User $user, array $data): Review
    {
        return DB::transaction(function () use ($product, $user, $data) {
            $orderItem = $this->findVerifyingOrderItem($product, $user);

            $values = [
                'order_item_id' => $orderItem?->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'is_verified_purchase' => $orderItem !== null,
                'status' => $orderItem !== null ? 'approved' : 'pending',
            ];

            // createOrFirst (not updateOrCreate) — two concurrent submits
            // (double-click, two tabs) can both miss each other's
            // uncommitted insert under READ COMMITTED; createOrFirst catches
            // the unique-index violation (see the 2026_08_21_000006
            // migration) and retries as a find instead of raising it, then
            // the fill+save below finishes the "or update" half a plain
            // updateOrCreate would otherwise have handled.
            $review = Review::createOrFirst(['product_id' => $product->id, 'user_id' => $user->id], $values);

            if (! $review->wasRecentlyCreated) {
                $review->fill($values)->save();
            }

            $this->recalculate($product);

            return $review->fresh(['images']);
        });
    }

    public function update(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $review->fill([
                'rating' => $data['rating'] ?? $review->rating,
                'title' => array_key_exists('title', $data) ? $data['title'] : $review->title,
                'body' => array_key_exists('body', $data) ? $data['body'] : $review->body,
                // Editing content re-enters moderation unless it's a verified
                // purchase, matching the same rule fresh submissions get.
                'status' => $review->is_verified_purchase ? 'approved' : 'pending',
            ])->save();

            $this->recalculate($review->product);

            return $review->fresh(['images']);
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $product = $review->product;
            $review->delete();
            $this->recalculate($product);
        });
    }

    public function updateStatus(Review $review, string $status): Review
    {
        return DB::transaction(function () use ($review, $status) {
            $review->update(['status' => $status]);
            $this->recalculate($review->product);

            return $review->fresh(['images']);
        });
    }

    private function findVerifyingOrderItem(Product $product, User $user): ?OrderItem
    {
        return OrderItem::whereHas('variant', fn ($q) => $q->where('product_id', $product->id))
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('order_status', 'delivered'))
            ->first();
    }

    private function recalculate(Product $product): void
    {
        $stats = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->selectRaw('avg(rating) as avg_rating, count(*) as review_count')
            ->first();

        $product->update([
            'avg_rating' => round((float) ($stats->avg_rating ?? 0), 2),
            'review_count' => (int) ($stats->review_count ?? 0),
        ]);
    }
}

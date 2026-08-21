"use client";

import { useWishlist } from "@/components/wishlist/WishlistProvider";

export function WishlistBadge() {
  const { items } = useWishlist();
  const count = items.length;

  if (count === 0) return null;

  return (
    <span
      key={count}
      className="animate-badge-pop absolute -top-1 -end-1 flex h-[17px] w-[17px] items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-background shadow-soft ring-2 ring-background"
    >
      {count > 9 ? "9+" : count}
    </span>
  );
}

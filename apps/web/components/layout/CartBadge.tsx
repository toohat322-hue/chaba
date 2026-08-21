"use client";

import { useCart } from "@/components/cart/CartProvider";

export function CartBadge() {
  const { cart } = useCart();
  const count = cart?.item_count ?? 0;

  if (count === 0) return null;

  return (
    <span
      key={count}
      className="animate-badge-pop absolute -top-1.5 -end-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-accent text-[11px] font-bold text-primary shadow-soft ring-2 ring-background"
    >
      {count > 9 ? "9+" : count}
    </span>
  );
}

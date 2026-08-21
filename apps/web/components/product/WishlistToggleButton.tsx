"use client";

import { useTranslations } from "next-intl";
import { useWishlist } from "@/components/wishlist/WishlistProvider";

function HeartIcon({ filled }: { filled: boolean }) {
  return (
    <svg viewBox="0 0 24 24" fill={filled ? "currentColor" : "none"} stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path
        d="M12 20s-7-4.4-9.5-8.8C.7 7.8 2.3 4 6 4c2 0 3.5 1.2 4.5 2.7C11.5 5.2 13 4 15 4c3.7 0 5.3 3.8 3.5 7.2C19 15.6 12 20 12 20z"
        strokeLinejoin="round"
      />
    </svg>
  );
}

export function WishlistToggleButton({ productId, className }: { productId: string; className?: string }) {
  const t = useTranslations("Nav");
  const { has, toggle } = useWishlist();
  const active = has(productId);

  return (
    <button
      type="button"
      onClick={(event) => {
        // ProductCard nests this inside a <Link> to the product page —
        // toggling the wishlist must not trigger that navigation.
        event.preventDefault();
        event.stopPropagation();
        void toggle(productId);
      }}
      aria-label={t("wishlist")}
      aria-pressed={active}
      className={`rounded-full bg-white/90 p-2 shadow-soft transition-colors hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 ${active ? "text-error" : "text-ink/60 hover:text-error"} ${className ?? ""}`}
    >
      <HeartIcon filled={active} />
    </button>
  );
}

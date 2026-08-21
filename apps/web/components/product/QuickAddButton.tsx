"use client";

import { useState, type MouseEvent } from "react";
import { useTranslations } from "next-intl";
import { useCart } from "@/components/cart/CartProvider";
import { useCartDrawer } from "@/components/cart/CartDrawerProvider";
import { useToast } from "@/components/ui/Toast";
import { getProduct, type ProductListItem } from "@/lib/api";

type Status = "idle" | "loading" | "added" | "error";

function Spinner() {
  return (
    <svg viewBox="0 0 24 24" fill="none" className="h-4 w-4 animate-spin">
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="2.5" opacity="0.25" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
    </svg>
  );
}

function CartPlusIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-4.5 w-4.5">
      <path d="M3 4h2l1.6 10.2A2 2 0 0 0 8.6 16h7.8a2 2 0 0 0 2-1.6L20 7H5.6" strokeLinecap="round" strokeLinejoin="round" />
      <circle cx="9" cy="20" r="1.1" fill="currentColor" stroke="none" />
      <circle cx="17" cy="20" r="1.1" fill="currentColor" stroke="none" />
      <path d="M17 2v5M14.5 4.5h5" strokeLinecap="round" />
    </svg>
  );
}

function CheckIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.25" className="h-4.5 w-4.5">
      <path d="M5 12.5l4.5 4.5L19 7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

// Product listing rows carry no variant info (PRD contract for /products,
// /featured-products, /search) — quick-add resolves the real default variant
// via the existing GET /products/{slug} call, then reuses the existing
// POST /cart/items call. No backend change, no guessed/fabricated variant id.
export function QuickAddButton({ product, className }: { product: ProductListItem; className?: string }) {
  const { addItem } = useCart();
  const { open: openCartDrawer } = useCartDrawer();
  const { show: showToast } = useToast();
  const t = useTranslations("Product");
  const tCart = useTranslations("Cart");
  const [status, setStatus] = useState<Status>("idle");

  const disabled = !product.in_stock || status === "loading";

  async function handleClick(event: MouseEvent) {
    // ProductCard nests this inside a <Link> to the product page — adding to
    // cart must not trigger that navigation.
    event.preventDefault();
    event.stopPropagation();
    if (disabled) return;

    setStatus("loading");
    try {
      const detail = await getProduct(product.slug);
      const variant = detail.variants.find((item) => item.available_quantity > 0) ?? detail.variants[0];
      if (!variant) throw new Error("no_variant_available");

      await addItem(variant.id, 1);
      setStatus("added");
      showToast({
        message: t("addedToCart"),
        actionLabel: tCart("viewCart"),
        onAction: openCartDrawer,
      });
      setTimeout(() => setStatus("idle"), 2000);
    } catch {
      setStatus("error");
      setTimeout(() => setStatus("idle"), 2000);
    }
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={disabled}
      aria-label={t("addToCart")}
      title={status === "error" ? t("addToCartError") : t("addToCart")}
      className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-all duration-300 ease-out hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:translate-y-0 disabled:bg-primary/10 disabled:text-primary/30 ${
        status === "added"
          ? "bg-success text-background"
          : status === "error"
            ? "bg-error text-background"
            : "bg-primary text-background hover:bg-primary/90"
      } ${className ?? ""}`}
    >
      {status === "loading" ? <Spinner /> : status === "added" ? <CheckIcon /> : <CartPlusIcon />}
    </button>
  );
}

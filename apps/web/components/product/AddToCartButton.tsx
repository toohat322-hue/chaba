"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useCart } from "@/components/cart/CartProvider";
import { useCartDrawer } from "@/components/cart/CartDrawerProvider";
import { useToast } from "@/components/ui/Toast";
import { ApiError } from "@/lib/api";

type Status = "idle" | "loading" | "added" | "error";

function Spinner() {
  return (
    <svg viewBox="0 0 24 24" fill="none" className="h-4 w-4 animate-spin">
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="2.5" opacity="0.25" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
    </svg>
  );
}

export function AddToCartButton({
  items,
  disabled,
}: {
  /** One entry per selected size — each size/variant is added to the cart
   * as its own independent line item. */
  items: { variantId: string; quantity: number }[];
  disabled?: boolean;
}) {
  const { addItem } = useCart();
  const { open: openCartDrawer } = useCartDrawer();
  const { show: showToast } = useToast();
  const t = useTranslations("Product");
  const tCart = useTranslations("Cart");
  const [status, setStatus] = useState<Status>("idle");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
  const isEmpty = items.length === 0;

  async function handleClick() {
    setStatus("loading");
    setErrorMessage(null);

    try {
      // Sequential, not Promise.all: each call's response replaces the
      // whole cart state in CartProvider, so awaiting one at a time
      // guarantees the final state reflects every successful add rather
      // than whichever parallel response happens to land last.
      for (const item of items) {
        await addItem(item.variantId, item.quantity);
      }
      setStatus("added");
      showToast({
        message: t("addedToCart"),
        actionLabel: tCart("viewCart"),
        onAction: openCartDrawer,
      });
      setTimeout(() => setStatus("idle"), 2000);
    } catch (error) {
      setStatus("error");
      setErrorMessage(error instanceof ApiError ? error.message : t("addToCartError"));
    }
  }

  return (
    <div className="flex flex-col gap-2">
      <button
        type="button"
        onClick={handleClick}
        disabled={disabled || isEmpty || status === "loading"}
        className="flex items-center justify-center gap-2 rounded-full bg-primary px-6 py-3.5 text-body font-semibold text-background transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-primary/15 disabled:text-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      >
        {status === "loading" && <Spinner />}
        {disabled
          ? t("outOfStock")
          : status === "added"
            ? t("addedToCart")
            : isEmpty
              ? t("addToCart")
              : t("addToCartWithCount", { count: totalQuantity })}
      </button>
      {errorMessage && (
        <p className="text-small text-error" role="alert">
          {errorMessage}
        </p>
      )}
    </div>
  );
}

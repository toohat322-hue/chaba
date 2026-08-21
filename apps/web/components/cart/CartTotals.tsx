"use client";

import { useTranslations } from "next-intl";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

export function CartTotals({
  subtotal,
  discount = 0,
  deliveryFee,
  freeShipping = false,
  total,
  locale,
  size = "default",
}: {
  subtotal: number;
  discount?: number;
  deliveryFee?: number;
  freeShipping?: boolean;
  total: number;
  locale: Locale;
  size?: "default" | "compact";
}) {
  const tCart = useTranslations("Cart");
  const tCheckout = useTranslations("Checkout");

  const rowText = size === "compact" ? "text-small" : "text-body";
  const totalText = size === "compact" ? "text-h3" : "text-h2";

  return (
    <div className={`space-y-1.5 ${rowText}`}>
      <div className="flex items-center justify-between text-ink/70">
        <span>{tCart("subtotal")}</span>
        <span>{formatPrice(subtotal, locale)}</span>
      </div>

      {discount > 0 && (
        <div className="flex items-center justify-between text-success">
          <span>{tCart("discount")}</span>
          <span>-{formatPrice(discount, locale)}</span>
        </div>
      )}

      {deliveryFee !== undefined && (
        <div className="flex items-center justify-between text-ink/70">
          <span>{tCheckout("deliveryFee")}</span>
          <span>{freeShipping ? tCheckout("freeShipping") : formatPrice(deliveryFee, locale)}</span>
        </div>
      )}

      <div className={`flex items-center justify-between pt-2 font-semibold text-primary ${totalText}`}>
        <span>{tCart("total")}</span>
        <span>{formatPrice(total, locale)}</span>
      </div>
    </div>
  );
}

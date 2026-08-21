"use client";

import { useMemo, useState, type ReactNode } from "react";
import { useTranslations } from "next-intl";
import type { ProductVariantDetail } from "@/lib/api";
import { formatPrice, formatSize } from "@/lib/currency";
import { QuantityStepper } from "@/components/product/QuantityStepper";
import { AddToCartButton } from "@/components/product/AddToCartButton";

type Locale = "ar" | "fr" | "en";

function CheckBadge() {
  return (
    <span className="absolute end-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-accent text-primary">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3 w-3">
        <path d="M4 10l4 4 8-8" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    </span>
  );
}

export function VariantSelector({
  variants,
  locale,
  children,
}: {
  variants: ProductVariantDetail[];
  locale: Locale;
  children?: ReactNode;
}) {
  const t = useTranslations("Product");
  // Sizes read smallest-to-largest (10ml, 20ml, 50ml...) regardless of the
  // order the admin created them in — sort_order is still the fallback for
  // variants with no size_value (e.g. color-only variants), where there's
  // no numeric order to derive.
  const sorted = useMemo(
    () =>
      [...variants].sort((a, b) => {
        if (a.size_value != null && b.size_value != null) {
          return a.size_value - b.size_value;
        }
        return a.sort_order - b.sort_order;
      }),
    [variants],
  );

  // Each size is independent — variantId -> quantity. Absent or 0 means
  // "not added yet" (renders the "+ إضافة" control); no size pre-selects
  // another out, and there's no single shared quantity anymore.
  const [quantities, setQuantities] = useState<Record<string, number>>({});

  if (sorted.length === 0) return null;

  function setQty(variantId: string, next: number, max: number) {
    setQuantities((prev) => ({ ...prev, [variantId]: Math.max(0, Math.min(max, next)) }));
  }

  const totalQuantity = sorted.reduce((sum, v) => sum + (quantities[v.id] ?? 0), 0);
  const totalPrice = sorted.reduce((sum, v) => sum + v.price * (quantities[v.id] ?? 0), 0);
  const cartItems = sorted
    .filter((v) => (quantities[v.id] ?? 0) > 0)
    .map((v) => ({ variantId: v.id, quantity: quantities[v.id] }));

  return (
    <div className="flex flex-col gap-5">
      {children}

      <div>
        {sorted.length > 1 && <p className="mb-2.5 text-small font-semibold text-ink">{t("chooseSize")}</p>}
        <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
          {sorted.map((variant) => {
            const qty = quantities[variant.id] ?? 0;
            const isActive = qty > 0;
            const isAvailable = variant.available_quantity > 0;
            const sizeLabel =
              variant.size_value != null && variant.size_unit
                ? formatSize(variant.size_value, variant.size_unit, locale)
                : [variant.size, variant.color].filter(Boolean).join(" / ") || variant.sku;

            return (
              <div
                key={variant.id}
                className={`relative flex flex-col items-center gap-1.5 rounded-xl border-2 px-3 py-3 text-center transition-colors ${
                  isActive
                    ? "border-accent bg-accent/10"
                    : isAvailable
                      ? "border-primary/15 hover:border-primary/35"
                      : "border-primary/10 opacity-50"
                }`}
              >
                {isActive && <CheckBadge />}
                <span className="text-small font-semibold text-ink">{sizeLabel}</span>
                {isAvailable ? (
                  <span className="flex items-baseline gap-1.5">
                    <span className="text-caption text-ink/60">{formatPrice(variant.price, locale)}</span>
                    {variant.compare_at_price != null && variant.compare_at_price > variant.price && (
                      <span className="text-caption text-ink/35 line-through">
                        {formatPrice(variant.compare_at_price, locale)}
                      </span>
                    )}
                  </span>
                ) : (
                  <span className="text-caption text-error">{t("unavailable")}</span>
                )}

                {isAvailable && (
                  <div className="mt-1">
                    {isActive ? (
                      <QuantityStepper
                        quantity={qty}
                        min={0}
                        max={variant.available_quantity}
                        onChange={(next) => setQty(variant.id, next, variant.available_quantity)}
                      />
                    ) : (
                      <button
                        type="button"
                        onClick={() => setQty(variant.id, 1, variant.available_quantity)}
                        className="rounded-full border border-accent/40 px-3 py-1 text-caption font-semibold text-primary transition-colors hover:bg-accent/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
                      >
                        + {t("addSize")}
                      </button>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      <div className="flex items-center justify-between rounded-xl bg-primary/5 px-4 py-3">
        <span className="text-small text-ink/70">{t("totalQuantityLabel", { count: totalQuantity })}</span>
        <span className="text-h3 font-semibold text-primary">{formatPrice(totalPrice, locale)}</span>
      </div>

      {/* Desktop/tablet: inline button. Hidden on mobile in favor of the sticky bar below, per "prominent or easily reachable on mobile". */}
      <div className="hidden sm:block">
        <AddToCartButton items={cartItems} />
      </div>

      <div
        className="fixed inset-x-0 bottom-0 z-40 flex items-center gap-4 border-t border-primary/10 bg-background/95 px-4 py-3 backdrop-blur sm:hidden"
        style={{ paddingBottom: "max(0.75rem, env(safe-area-inset-bottom))" }}
      >
        <div className="min-w-0 flex-1">
          <p className="text-caption text-ink/60">{t("totalQuantityLabel", { count: totalQuantity })}</p>
          <p className="text-h3 font-semibold text-primary">{formatPrice(totalPrice, locale)}</p>
        </div>
        <div className="shrink-0">
          <AddToCartButton items={cartItems} />
        </div>
      </div>
      {/* Spacer so the fixed mobile bar never overlaps the content beneath it (reviews section etc). */}
      <div className="h-16 sm:hidden" aria-hidden="true" />
    </div>
  );
}

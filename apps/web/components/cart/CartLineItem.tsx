"use client";

import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { formatPrice, formatSize } from "@/lib/currency";
import { Link } from "@/i18n/navigation";
import { PerfumeGlyph } from "@/components/product/PerfumeGlyph";
import { useCart } from "./CartProvider";
import type { CartItemDetail } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export function CartLineItem({ item, compact = false }: { item: CartItemDetail; compact?: boolean }) {
  const locale = useLocale() as Locale;
  const t = useTranslations("Cart");
  const { updateItem, removeItem } = useCart();
  // Guards two things the +/- buttons didn't before: rapid clicks racing
  // each other off the same stale item.quantity (each click computed
  // quantity±1 from render-time props, so quick double/triple-clicks lost
  // updates), and "-" at quantity 1 silently sending quantity:0 to the API
  // instead of going through the explicit remove action.
  const [pending, setPending] = useState(false);

  const sizeLabel = item.size_value != null && item.size_unit ? formatSize(item.size_value, item.size_unit, locale) : item.size;
  const variantLabel = [item.color, sizeLabel].filter(Boolean).join(" / ");
  const atMax = item.quantity >= item.available_quantity;
  const imageSize = compact ? "h-16 w-16" : "h-24 w-24";

  function changeQuantity(nextQuantity: number) {
    setPending(true);
    updateItem(item.id, nextQuantity)
      .catch((error) => console.error("Failed to update item", error))
      .finally(() => setPending(false));
  }

  return (
    <div className={`flex gap-4 border-b border-primary/10 last:border-b-0 ${compact ? "py-4" : "py-6"}`}>
      <Link href={`/products/${item.product_slug}`} className={`${imageSize} shrink-0 overflow-hidden rounded-xl`}>
        {item.image_url ? (
          // eslint-disable-next-line @next/next/no-img-element -- remote catalog image
          <img src={item.image_url} alt={item.product_name[locale]} className="h-full w-full object-cover" />
        ) : (
          <PerfumeGlyph className="h-full w-full" />
        )}
      </Link>

      <div className="flex flex-1 flex-col gap-2">
        <div className="flex items-start justify-between gap-4">
          <div>
            <Link href={`/products/${item.product_slug}`} className="text-body font-medium text-ink hover:text-primary">
              {item.product_name[locale]}
            </Link>
            {variantLabel && <p className="text-small text-ink/50">{variantLabel}</p>}
          </div>
          <button
            type="button"
            onClick={() => removeItem(item.id).catch((error) => console.error("Failed to remove item", error))}
            className="text-small text-ink/50 hover:text-error"
          >
            {t("remove")}
          </button>
        </div>

        {item.price_changed && <p className="text-caption text-warning">{t("priceChanged")}</p>}
        {item.exceeds_stock && (
          <p className="text-caption text-error">{t("exceedsStock", { count: item.available_quantity })}</p>
        )}

        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3 rounded-full border border-primary/15 px-3 py-1">
            <button
              type="button"
              onClick={() => changeQuantity(item.quantity - 1)}
              disabled={pending || item.quantity <= 1}
              className="text-body text-ink/70 hover:text-primary disabled:opacity-30"
              aria-label="-"
            >
              −
            </button>
            <span className="w-6 text-center text-body text-ink">{item.quantity}</span>
            <button
              type="button"
              onClick={() => changeQuantity(item.quantity + 1)}
              disabled={pending || atMax}
              className="text-body text-ink/70 hover:text-primary disabled:opacity-30"
              aria-label="+"
            >
              +
            </button>
          </div>
          <span className="text-body font-semibold text-primary">
            {formatPrice(item.current_price * item.quantity, locale)}
          </span>
        </div>
      </div>
    </div>
  );
}

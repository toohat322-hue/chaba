"use client";

import { useTranslations } from "next-intl";
import { formatPrice } from "@/lib/currency";
import type { ProductVariantDetail } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

function CheckIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="h-3.5 w-3.5">
      <path d="M4 10l4 4 8-8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function AlertIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="h-3.5 w-3.5">
      <path d="M10 3l8 14H2l8-14z" strokeLinejoin="round" />
      <path d="M10 8v4" strokeLinecap="round" />
      <circle cx="10" cy="14.5" r="0.75" fill="currentColor" stroke="none" />
    </svg>
  );
}

function XIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="h-3.5 w-3.5">
      <path d="M6 6l8 8M14 6l-8 8" strokeLinecap="round" />
    </svg>
  );
}

// PRD §7.5: stock status copy — in stock / low stock ("only N left") / out of stock.
// Status is conveyed by text + icon together, not color alone.
export function VariantInfo({
  variant,
  comparePrice,
  locale,
  compact = false,
  hidePrice = false,
}: {
  variant: ProductVariantDetail;
  comparePrice: number | null;
  locale: Locale;
  compact?: boolean;
  /** The premium size cards already show each variant's own price — avoid
   * showing it a second time right below them on desktop. The compact
   * mobile sticky bar still wants it (the cards may be scrolled out of view). */
  hidePrice?: boolean;
}) {
  const t = useTranslations("Product");

  const outOfStock = variant.available_quantity <= 0;
  const stockLabel = outOfStock ? t("outOfStock") : variant.low_stock ? t("lowStock", { count: variant.available_quantity }) : t("inStock");
  const stockColor = outOfStock ? "text-error" : variant.low_stock ? "text-warning" : "text-success";
  const StockIcon = outOfStock ? XIcon : variant.low_stock ? AlertIcon : CheckIcon;

  const priceText = compact ? "text-h3" : "text-h1";

  return (
    <div className={compact ? "flex items-center gap-3" : "flex flex-col gap-3"}>
      {!hidePrice && (
        <div className="flex items-baseline gap-3">
          <span className={`${priceText} font-semibold text-primary`}>{formatPrice(variant.price, locale)}</span>
          {comparePrice && comparePrice > variant.price && (
            <span className="text-body text-ink/40 line-through">{formatPrice(comparePrice, locale)}</span>
          )}
        </div>
      )}
      {!compact && (
        <span className={`flex items-center gap-1.5 text-small font-medium ${stockColor}`}>
          <StockIcon />
          {stockLabel}
        </span>
      )}
    </div>
  );
}

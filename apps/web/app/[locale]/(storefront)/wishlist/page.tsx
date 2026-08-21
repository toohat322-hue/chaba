"use client";

import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useWishlist } from "@/components/wishlist/WishlistProvider";
import { AddToCartButton } from "@/components/product/AddToCartButton";
import { PerfumeGlyph } from "@/components/product/PerfumeGlyph";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

function RemoveIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-4 w-4">
      <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
    </svg>
  );
}

export default function WishlistPage() {
  const locale = useLocale() as Locale;
  const t = useTranslations("Wishlist");
  const tProduct = useTranslations("Product");
  const { items, loading, remove } = useWishlist();
  const tLoading = useTranslations("Loading");

  if (loading) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  if (items.length === 0) {
    return (
      <div className="mx-auto flex max-w-3xl flex-col items-center gap-4 px-6 py-24 text-center">
        <h1 className="text-h1 font-semibold text-primary">{t("title")}</h1>
        <p className="text-body text-ink/60">{t("empty")}</p>
        <Link
          href="/"
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
        >
          {t("browseCategories")}
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
      <h1 className="mb-10 text-h1 font-semibold text-primary">{t("title")}</h1>

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {items.map((item) => (
          <div
            key={item.id}
            className="flex flex-col overflow-hidden rounded-2xl border border-primary/8 bg-white shadow-soft"
          >
            <div className="relative aspect-square overflow-hidden bg-primary/5">
              <Link href={`/products/${item.product_slug}`} className="block h-full w-full">
                {item.image_url ? (
                  // eslint-disable-next-line @next/next/no-img-element -- remote catalog image
                  <img src={item.image_url} alt="" loading="lazy" className="h-full w-full object-cover" />
                ) : (
                  <PerfumeGlyph className="h-full w-full" />
                )}
              </Link>
              <button
                type="button"
                onClick={() => remove(item.product_id)}
                aria-label={t("remove")}
                className="absolute top-3 end-3 rounded-full bg-white/90 p-2 text-ink/60 shadow-soft transition-colors hover:bg-white hover:text-error focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
              >
                <RemoveIcon />
              </button>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-4">
              <Link href={`/products/${item.product_slug}`}>
                <h3 className="line-clamp-2 text-body font-medium text-ink hover:text-primary">
                  {item.name[locale]}
                </h3>
              </Link>

              <div className="flex items-baseline gap-2">
                <span className="text-h3 font-semibold text-primary">{formatPrice(item.price, locale)}</span>
                {item.compare_at_price && (
                  <span className="text-small text-ink/40 line-through">
                    {formatPrice(item.compare_at_price, locale)}
                  </span>
                )}
              </div>

              <span className={`text-small font-medium ${item.in_stock ? "text-success" : "text-error"}`}>
                {item.in_stock ? tProduct("inStock") : tProduct("outOfStock")}
              </span>

              <div className="mt-auto pt-2">
                <AddToCartButton items={[{ variantId: item.variant_id, quantity: 1 }]} disabled={!item.in_stock} />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

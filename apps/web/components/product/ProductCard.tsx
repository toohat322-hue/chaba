import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { formatPrice } from "@/lib/currency";
import type { ProductListItem } from "@/lib/api";
import { Link } from "@/i18n/navigation";
import { useImageRetry } from "@/lib/useImageRetry";
import { StarRating } from "@/components/reviews/StarRating";
import { PerfumeGlyph } from "./PerfumeGlyph";
import { WishlistToggleButton } from "./WishlistToggleButton";
import { QuickAddButton } from "./QuickAddButton";

function badgeFor(product: ProductListItem): "sale" | "bestseller" | null {
  if (product.compare_at_price && product.compare_at_price > product.price) {
    return "sale";
  }
  if (product.featured) {
    return "bestseller";
  }
  return null;
}

const BADGE_LABEL_KEY = {
  bestseller: "badgeBestseller",
  sale: "badgeSale",
} as const;

export function ProductCard({ product }: { product: ProductListItem }) {
  const locale = useLocale() as "ar" | "fr" | "en";
  const t = useTranslations("Product");
  const badge = badgeFor(product);
  const image = useImageRetry();

  return (
    <Link
      href={`/products/${product.slug}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-primary/8 bg-white shadow-soft transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lift focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
    >
      <div className="relative aspect-square overflow-hidden bg-primary/5">
        {product.image_url && !image.failed ? (
          <Image
            key={image.key}
            src={product.image_url}
            alt={product.image_alt ?? product.name[locale]}
            fill
            sizes="(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw"
            className={`object-cover transition-transform duration-300 ease-out group-hover:scale-105 ${
              product.in_stock ? "" : "grayscale"
            }`}
            onError={image.onError}
          />
        ) : (
          <PerfumeGlyph
            className={`h-full w-full transition-transform duration-300 ease-out group-hover:scale-105 ${
              product.in_stock ? "" : "grayscale"
            }`}
          />
        )}
        {!product.in_stock && (
          <div className="absolute inset-0 flex items-center justify-center bg-ink/25">
            <span className="rounded-full bg-background/95 px-4 py-1.5 text-small font-semibold text-ink">
              {t("outOfStock")}
            </span>
          </div>
        )}
        {badge && product.in_stock && (
          <span className="absolute top-3 start-3 rounded-full bg-accent px-3 py-1 text-caption font-semibold text-primary">
            {t(BADGE_LABEL_KEY[badge])}
          </span>
        )}
        <WishlistToggleButton productId={product.id} className="absolute top-3 end-3" />
      </div>
      <div className="flex flex-1 flex-col gap-1.5 p-4 text-start">
        <h3 className="line-clamp-2 text-body font-medium text-ink">{product.name[locale]}</h3>
        {product.review_count > 0 && (
          <div className="flex items-center gap-1.5">
            <StarRating value={product.avg_rating} />
            <span className="text-caption text-ink/50">({product.review_count})</span>
          </div>
        )}
        <div className="mt-1 flex items-center justify-between gap-2">
          <div className="flex items-baseline gap-2">
            <span className="text-h3 font-semibold text-primary">
              {formatPrice(product.price, locale)}
            </span>
            {product.compare_at_price && (
              <span className="text-small text-ink/40 line-through">
                {formatPrice(product.compare_at_price, locale)}
              </span>
            )}
          </div>
          <QuickAddButton product={product} />
        </div>
      </div>
    </Link>
  );
}

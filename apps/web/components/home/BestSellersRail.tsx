import { getTranslations } from "next-intl/server";
import { getFeaturedProducts, type ProductListItem } from "@/lib/api";
import { Link } from "@/i18n/navigation";
import { ProductGrid } from "@/components/catalog/ProductGrid";

export async function BestSellersRail() {
  const t = await getTranslations("BestSellers");

  // No hardcoded fallback products on failure — an empty section (with a
  // console-visible cause) is honest; fabricated products aren't. Error and
  // "genuinely no featured products" are kept distinct so a real outage
  // doesn't read to the shopper as "we just have nothing on sale."
  const { items: products, hadError } = await getFeaturedProducts()
    .then((items) => ({ items, hadError: false }))
    .catch((error) => {
      console.error("Failed to load featured products", error);
      return { items: [] as ProductListItem[], hadError: true };
    });

  return (
    <section className="mx-auto max-w-7xl px-6 py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 className="flex items-center gap-3 text-h1 font-semibold text-primary">
            <span
              className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent/15 text-accent"
              aria-hidden="true"
            >
              <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path
                  fill="currentColor"
                  d="M12 21c-4.4 0-8-3.1-8-7.5C4 9 7 5.5 8.5 3c.3 2.5 1.5 4 3 4.5C10 5 11 3 12 2c1.5 3 4 5 4 9 0 1-.3 2-.8 2.8.5-.3.8-1 .8-1.8 0-1.5-.5-2.5-1-3.5 2 1.5 3 4 3 6.5 0 4.4-3.6 7-8 7Z"
                />
              </svg>
            </span>
            {t("heading")}
          </h2>
          <p className="mt-2 text-body text-ink/70">{t("subheading")}</p>
        </div>
        <Link href="/category/exclusive-offers" className="text-body font-medium text-primary hover:text-accent">
          {t("viewAll")}
        </Link>
      </div>

      {products.length > 0 ? (
        <div className="mt-8">
          <ProductGrid products={products} />
        </div>
      ) : hadError ? (
        <div className="mt-8 flex flex-col items-center gap-2 rounded-2xl bg-primary/5 px-6 py-12 text-center">
          <p className="text-body font-medium text-ink/70">{t("error")}</p>
        </div>
      ) : (
        <p className="mt-8 text-body text-ink/50">{t("empty")}</p>
      )}
    </section>
  );
}

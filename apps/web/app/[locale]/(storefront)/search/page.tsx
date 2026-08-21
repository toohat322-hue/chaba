import { getTranslations } from "next-intl/server";
import { search } from "@/lib/api";
import { canonicalUrl } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { ProductGrid } from "@/components/catalog/ProductGrid";
import { SortSelect } from "@/components/catalog/SortSelect";
import { Pagination } from "@/components/catalog/Pagination";

type Props = {
  params: Promise<{ locale: string }>;
  searchParams: Promise<{ q?: string; sort?: string; page?: string }>;
};

const VALID_SORTS = ["newest", "price_asc", "price_desc", "rating"] as const;

export const dynamic = "force-dynamic";

export async function generateMetadata({ params, searchParams }: Props) {
  const { locale } = await params;
  const { q } = await searchParams;
  const query = (q ?? "").trim();
  const t = await getTranslations("Search");

  return {
    title: query ? t("metaTitleFor", { query }) : t("heading"),
    alternates: { canonical: canonicalUrl("/search", locale) },
    // Internal search results are low/duplicate-value for organic discovery
    // and infinite in variety (any query string is a "page") — a real
    // per-page noindex is used instead of the sitewide crawl-block that
    // used to cover this route (see app/robots.ts), since a crawl-block
    // alone would keep Google from ever seeing this directive.
    robots: { index: false, follow: true },
  };
}

export default async function SearchPage({ searchParams }: Props) {
  const { q, sort: rawSort, page: rawPage } = await searchParams;
  const query = (q ?? "").trim();
  const sort = VALID_SORTS.includes(rawSort as (typeof VALID_SORTS)[number])
    ? (rawSort as (typeof VALID_SORTS)[number])
    : undefined;
  const page = Math.max(1, Number(rawPage) || 1);

  const [tNav, tSearch, result] = await Promise.all([
    getTranslations("Nav"),
    getTranslations("Search"),
    search(query, { sort, page }),
  ]);

  const items = result.items.length > 0 ? result.items : (result.suggestions ?? []);
  const isFallback = Boolean(result.fallback) || Boolean(result.no_results);

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: tSearch("heading") }]} />

      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-h1 font-semibold text-primary">
            {query ? tSearch("resultsFor", { query }) : tSearch("heading")}
          </h1>
          {!isFallback && result.items.length > 0 && <SortSelect current={sort ?? ""} includeRelevance />}
        </div>

        {result.no_results && <p className="mt-3 text-body text-ink/60">{tSearch("noResultsFor", { query })}</p>}

        {items.length > 0 ? (
          <>
            {isFallback && (
              <h2 className="mb-4 mt-10 text-h3 font-semibold text-ink">{tSearch("popularProducts")}</h2>
            )}
            <div className={isFallback ? "" : "mt-10"}>
              <ProductGrid products={items} />
            </div>
            {!isFallback && result.meta && (
              <Pagination
                basePath="/search"
                currentPage={result.meta.current_page}
                lastPage={result.meta.last_page}
                currentParams={{ q: query, sort }}
              />
            )}
          </>
        ) : (
          <p className="mt-8 text-body text-ink/50">{tSearch("noResultsFor", { query })}</p>
        )}
      </div>
    </>
  );
}

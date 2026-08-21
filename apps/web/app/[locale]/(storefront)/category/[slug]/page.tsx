import { notFound } from "next/navigation";
import { getTranslations } from "next-intl/server";
import { ApiError, getCategory, getProducts, resolveSlugRedirect } from "@/lib/api";
import { permanentRedirect } from "@/i18n/navigation";
import { languageAlternates, canonicalUrl, buildOpenGraph } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { ProductGrid } from "@/components/catalog/ProductGrid";
import { SortSelect } from "@/components/catalog/SortSelect";
import { Pagination } from "@/components/catalog/Pagination";

type Locale = "ar" | "fr" | "en";

type Props = {
  params: Promise<{ locale: string; slug: string }>;
  searchParams: Promise<{ sort?: string; page?: string }>;
};

const VALID_SORTS = ["newest", "price_asc", "price_desc", "rating"] as const;

async function loadCategory(slug: string) {
  try {
    return await getCategory(slug);
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }
    throw error;
  }
}

export async function generateMetadata({ params, searchParams }: Props) {
  const { locale, slug } = await params;
  const { page: rawPage } = await searchParams;
  const category = await loadCategory(slug);

  if (!category) return {};

  const rawTitle = category.name[locale as Locale];
  const t = await getTranslations({ locale, namespace: "Category" });
  // "Default SEO unless the admin overrides it" — same pattern as products:
  // seo_title/seo_description come from the admin category form, falling
  // back to a real (not boilerplate-empty) generated title/description.
  const title = category.seo_title || rawTitle;
  const description = category.seo_description || t("metaDescriptionFallback", { name: rawTitle });

  // Sort/filter query variants all consolidate onto the base category URL;
  // only pagination gets its own canonical (page 2 is genuinely different
  // content, not a facet of the same listing).
  const page = Math.max(1, Number(rawPage) || 1);
  const canonicalPath = page > 1 ? `/category/${slug}?page=${page}` : `/category/${slug}`;

  return {
    title,
    description,
    alternates: {
      canonical: canonicalUrl(canonicalPath, locale),
      languages: languageAlternates(`/category/${slug}`),
    },
    openGraph: buildOpenGraph({
      locale,
      title,
      description,
      image: category.image_url ? { url: category.image_url, alt: rawTitle } : undefined,
    }),
  };
}

export default async function CategoryPage({ params, searchParams }: Props) {
  const { locale, slug } = await params;
  const { sort: rawSort, page: rawPage } = await searchParams;
  const loc = locale as Locale;

  const category = await loadCategory(slug);
  if (!category) {
    const currentSlug = await resolveSlugRedirect("category", slug);
    if (currentSlug) {
      permanentRedirect({ href: `/category/${currentSlug}`, locale });
    }
    notFound();
  }

  const sort = VALID_SORTS.includes(rawSort as typeof VALID_SORTS[number])
    ? (rawSort as typeof VALID_SORTS[number])
    : "newest";
  const page = Math.max(1, Number(rawPage) || 1);

  const [{ items, meta }, tNav, tCategory] = await Promise.all([
    getProducts({ category: slug, sort, page }),
    getTranslations("Nav"),
    getTranslations("Category"),
  ]);

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: category.name[loc] }]} />

      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-h1 font-semibold text-primary">{category.name[loc]}</h1>
          <SortSelect current={sort} />
        </div>

        {items.length > 0 ? (
          <>
            <div className="mt-10">
              <ProductGrid products={items} />
            </div>
            <Pagination
              basePath={`/category/${slug}`}
              currentPage={meta.current_page}
              lastPage={meta.last_page}
              currentParams={{ sort }}
            />
          </>
        ) : (
          <p className="mt-8 text-body text-ink/50">{tCategory("noProducts")}</p>
        )}
      </div>
    </>
  );
}

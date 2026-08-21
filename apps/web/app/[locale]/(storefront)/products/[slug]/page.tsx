import { notFound } from "next/navigation";
import { getTranslations } from "next-intl/server";
import { ApiError, getProduct, resolveSlugRedirect } from "@/lib/api";
import { permanentRedirect } from "@/i18n/navigation";
import { languageAlternates, canonicalUrl, buildOpenGraph, buildProductJsonLd, SITE_URL } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { ProductGallery } from "@/components/product/ProductGallery";
import { VariantSelector } from "@/components/product/VariantSelector";
import { ProductGrid } from "@/components/catalog/ProductGrid";
import { StarRating } from "@/components/reviews/StarRating";
import { ReviewsSection } from "@/components/reviews/ReviewsSection";
import { WishlistToggleButton } from "@/components/product/WishlistToggleButton";

type Locale = "ar" | "fr" | "en";

type Props = {
  params: Promise<{ locale: string; slug: string }>;
};

async function loadProduct(slug: string) {
  try {
    return await getProduct(slug);
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }
    throw error;
  }
}

export async function generateMetadata({ params }: Props) {
  const { locale, slug } = await params;
  const product = await loadProduct(slug);

  if (!product) {
    return {};
  }

  const rawTitle = product.name[locale as Locale];
  // "Default SEO unless the admin overrides it" — seo_title/seo_description
  // are set via the admin product form; most products leave them blank, so
  // a real, still-unique-per-product title/description is generated here.
  const title = product.seo_title || `${rawTitle} | شابة`;
  const description = product.seo_description || product.description[locale as Locale]?.slice(0, 160);
  const productImage = product.images[0];

  return {
    title,
    description,
    alternates: {
      canonical: canonicalUrl(`/products/${slug}`, locale),
      languages: languageAlternates(`/products/${slug}`),
    },
    openGraph: buildOpenGraph({
      locale,
      title,
      description,
      image: productImage ? { url: productImage.url, alt: productImage.alt_text ?? rawTitle } : undefined,
    }),
  };
}

export default async function ProductPage({ params }: Props) {
  const { locale, slug } = await params;
  const loc = locale as Locale;

  const product = await loadProduct(slug);
  if (!product) {
    const currentSlug = await resolveSlugRedirect("product", slug);
    if (currentSlug) {
      permanentRedirect({ href: `/products/${currentSlug}`, locale });
    }
    notFound();
  }

  const [tNav, tProduct] = await Promise.all([
    getTranslations("Nav"),
    getTranslations("Product"),
  ]);

  const related = product.related.slice(0, 4);
  const productUrl = languageAlternates(`/products/${slug}`)[locale] ?? `${SITE_URL}/${locale}/products/${slug}`;
  const jsonLd = buildProductJsonLd({ product, locale: loc, url: productUrl });

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }} />

      <Breadcrumbs
        items={[
          { label: tNav("home"), href: "/" },
          ...(product.category
            ? [{ label: product.category.name[loc], href: `/category/${product.category.slug}` }]
            : []),
          { label: product.name[loc] },
        ]}
      />

      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 sm:py-12 md:grid-cols-2 md:gap-14 lg:px-8">
        {/* key={product.id}: without it, navigating from one product page to
            another doesn't remount this (same JSX position, App Router
            reconciles in place) — its activeIndex state survives with an
            index that may not exist in the new product's images, leaving
            every cross-fade layer at opacity-0 until a thumbnail is clicked. */}
        <ProductGallery key={product.id} images={product.images} title={product.name[loc]} />

        <div className="flex flex-col gap-7">
          <div>
            <div className="flex items-start justify-between gap-3">
              <h1 className="text-h1 font-semibold text-ink">{product.name[loc]}</h1>
              <WishlistToggleButton productId={product.id} className="shrink-0 border border-primary/10 bg-white" />
            </div>
            {product.review_count > 0 && (
              <div className="mt-3 flex items-center gap-2">
                <StarRating value={product.avg_rating} />
                <span className="text-small text-ink/60">
                  {product.avg_rating.toFixed(1)} ({product.review_count})
                </span>
              </div>
            )}
          </div>

          {product.variants.length > 0 ? (
            <VariantSelector key={product.id} variants={product.variants} locale={loc}>
              {product.description[loc] && (
                <p className="text-body leading-relaxed text-ink/70">{product.description[loc]}</p>
              )}
            </VariantSelector>
          ) : (
            // The description was previously only rendered as VariantSelector's
            // children, so a variant-less product (unpublished/misconfigured
            // catalog entry) showed no description at all.
            product.description[loc] && (
              <p className="text-body leading-relaxed text-ink/70">{product.description[loc]}</p>
            )
          )}
        </div>
      </div>

      {related.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
          <h2 className="mb-6 text-h2 font-semibold text-primary">{tProduct("relatedProducts")}</h2>
          <ProductGrid products={related} />
        </section>
      )}

      <ReviewsSection productSlug={product.slug} />
    </>
  );
}

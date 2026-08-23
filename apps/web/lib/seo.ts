import { getPathname } from "@/i18n/navigation";
import { routing } from "@/i18n/routing";
import type { Crumb } from "@/components/layout/Breadcrumbs";
import type { FooterData, ProductDetail } from "./api";

// `||` not `??` — an unset build-time env var can arrive as an empty string
// rather than undefined (see next.config.ts's apiOrigin for the full
// explanation), which `??` doesn't fall back on.
export const SITE_URL = (process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000").replace(/\/$/, "");

/**
 * Absolute canonical URL for one locale of a locale-agnostic path (same
 * path shape `languageAlternates` uses). Every indexable page should set
 * `alternates.canonical` to this so query-string variants (sort/filter/
 * pagination) all consolidate onto one canonical URL per Google's
 * faceted-navigation guidance, instead of each combination competing as a
 * separate near-duplicate page.
 */
export function canonicalUrl(path: string, locale: string): string {
  return `${SITE_URL}${getPathname({ locale, href: path })}`;
}

/**
 * Builds hreflang alternates for a locale-agnostic path (e.g. "/products/foo",
 * or "" for the home page) — every locale renders the exact same path
 * (no per-locale pathname translation is configured in routing.ts), so this
 * only needs to swap the locale prefix in.
 *
 * Only meaningful for pages that are actually indexable and equivalent
 * across all three locales (home, product, category) — see app/robots.ts
 * for everything else (cart, checkout, auth, admin, ...), which opts out of
 * indexing entirely rather than needing (possibly wrong) alternates.
 */
export function languageAlternates(href: string): Record<string, string> {
  return Object.fromEntries(
    routing.locales.map((locale) => [locale, `${SITE_URL}${getPathname({ locale, href })}`]),
  );
}

type OgImage = { url: string; width?: number; height?: number; alt?: string };

const DEFAULT_OG_IMAGE: OgImage = { url: "/brand/logo.png", width: 2000, height: 2000 };

/**
 * Next.js's metadata resolution replaces the whole `openGraph` object rather
 * than deep-merging it with the layout's — a page that returns its own
 * `openGraph: {title, description}` silently loses the layout's siteName/
 * locale/type/image unless it re-specifies them. This keeps that shared
 * shape in one place instead of duplicating it on every page.
 */
export function buildOpenGraph({
  locale,
  title,
  description,
  image = DEFAULT_OG_IMAGE,
}: {
  locale: string;
  title: string;
  description?: string;
  image?: OgImage;
}) {
  return {
    title,
    description,
    siteName: "CHABA",
    locale,
    type: "website" as const,
    images: [{ ...image, alt: image.alt ?? title }],
  };
}

/**
 * schema.org Product markup for rich snippets (price/availability/rating
 * in Google search results). Every field it needs is already present on
 * `ProductDetail`'s `variants[]` (each carries its own effective price and
 * `available_quantity`) — no new API field, same data VariantSelector.tsx
 * already reads to pick a default size and show stock status.
 */
export function buildProductJsonLd({
  product,
  locale,
  url,
}: {
  product: ProductDetail;
  locale: "ar" | "fr" | "en";
  url: string;
}) {
  // ProductDetailResource already filters `variants[]` down to is_active
  // variants server-side, so no re-filtering is needed here.
  const cheapest = product.variants.reduce<(typeof product.variants)[number] | null>(
    (min, variant) => (min === null || variant.price < min.price ? variant : min),
    null,
  );
  const inStock = product.variants.some((v) => v.available_quantity > 0);

  return {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.name[locale],
    description: product.description[locale] || undefined,
    image: product.images.map((image) => image.url),
    sku: cheapest?.sku,
    offers: {
      "@type": "Offer",
      url,
      priceCurrency: "DZD",
      price: cheapest ? (cheapest.price / 100).toFixed(2) : undefined,
      availability: inStock ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
    },
    ...(product.review_count > 0 && {
      aggregateRating: {
        "@type": "AggregateRating",
        ratingValue: product.avg_rating,
        reviewCount: product.review_count,
      },
    }),
  };
}

/**
 * schema.org Organization + WebSite markup, rendered once site-wide (the
 * storefront layout) rather than per page. Only ever built from data that's
 * genuinely real: the brand name/logo already hardcoded consistently across
 * `layout.tsx`/`manifest.ts`, `SITE_URL`, and the store's real social links
 * (from the existing `/footer` endpoint) — never invented profiles.
 */
export function buildOrganizationJsonLd(footer: FooterData | null) {
  const sameAs = (footer?.socialLinks ?? []).map((link) => link.url);

  return {
    "@context": "https://schema.org",
    "@type": "Organization",
    name: "CHABA",
    url: SITE_URL,
    logo: `${SITE_URL}/brand/logo.png`,
    ...(sameAs.length > 0 && { sameAs }),
  };
}

export function buildWebSiteJsonLd(locale: string) {
  return {
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: "CHABA",
    url: canonicalUrl("/", locale),
    potentialAction: {
      "@type": "SearchAction",
      target: `${canonicalUrl("/search", locale)}?q={search_term_string}`,
      "query-input": "required name=search_term_string",
    },
  };
}

/**
 * schema.org BreadcrumbList mirroring the visible <Breadcrumbs> trail —
 * built from the exact same `items` prop so the structured data can never
 * drift from what's actually on the page. Only items with an `href` become
 * an Item (the final, current-page crumb has none); position is 1-indexed
 * per the schema.org spec.
 */
export function buildBreadcrumbJsonLd(items: Crumb[], locale: string) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.label,
      ...(item.href && { item: canonicalUrl(item.href, locale) }),
    })),
  };
}

/**
 * schema.org FAQPage markup for the /faq page, built from the same
 * translated Q&A array the visible accordion already renders — real
 * content only, never generated/duplicated questions.
 */
export function buildFaqJsonLd(items: { q: string; a: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((item) => ({
      "@type": "Question",
      name: item.q,
      acceptedAnswer: { "@type": "Answer", text: item.a },
    })),
  };
}

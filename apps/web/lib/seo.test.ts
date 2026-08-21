import { describe, expect, it, vi } from "vitest";
import {
  buildProductJsonLd,
  buildOrganizationJsonLd,
  buildWebSiteJsonLd,
  buildBreadcrumbJsonLd,
  buildFaqJsonLd,
  canonicalUrl,
} from "./seo";
import type { FooterData, ProductDetail } from "./api";

// seo.ts imports getPathname from @/i18n/navigation for languageAlternates()/
// canonicalUrl(), which pulls in next/navigation — unresolvable under
// Vitest's plain jsdom environment. Same narrowly-scoped mock as
// HeroSlider.test.tsx, just for the one export this module needs, standing
// in for next-intl's real locale-prefixing (routing.ts has no `pathnames`
// translation map, so the real getPathname just prefixes /{locale} onto the
// path — this mirrors that exactly). vi.mock calls are hoisted above
// imports by Vitest, so ordering here is fine.
vi.mock("@/i18n/navigation", () => ({
  getPathname: ({ locale, href }: { locale: string; href: string }) => `/${locale}${href}`,
}));

function makeProduct(overrides: Partial<ProductDetail> = {}): ProductDetail {
  return {
    id: "p1",
    slug: "chaba-signature",
    name: { ar: "شابة التوقيعي", fr: "CHABA Signature", en: "CHABA Signature" },
    description: { ar: "وصف", fr: "Description", en: "Description" },
    base_price: 350000,
    compare_at_price: null,
    status: "active",
    featured: false,
    seo_title: null,
    seo_description: null,
    updated_at: "2026-01-01T00:00:00Z",
    avg_rating: 0,
    review_count: 0,
    images: [{ url: "https://cdn.example.com/a.jpg", alt_text: null, variant_id: null }],
    variants: [
      {
        id: "v1",
        sku: "CHB-10ML",
        color: null,
        size: null,
        size_value: 10,
        size_unit: "ml",
        price: 350000,
        compare_at_price: null,
        sort_order: 0,
        available_quantity: 20,
        low_stock: false,
      },
      {
        id: "v2",
        sku: "CHB-50ML",
        color: null,
        size: null,
        size_value: 50,
        size_unit: "ml",
        price: 1200000,
        compare_at_price: null,
        sort_order: 1,
        available_quantity: 0,
        low_stock: false,
      },
    ],
    related: [],
    ...overrides,
  };
}

describe("buildProductJsonLd", () => {
  it("uses the cheapest variant's price, converted from centimes to DZD", () => {
    const jsonLd = buildProductJsonLd({ product: makeProduct(), locale: "en", url: "https://chaba.dz/en/products/chaba-signature" });

    expect(jsonLd.offers.price).toBe("3500.00");
    expect(jsonLd.offers.priceCurrency).toBe("DZD");
    expect(jsonLd.sku).toBe("CHB-10ML");
  });

  it("reports InStock when any variant has stock, even if others don't", () => {
    const jsonLd = buildProductJsonLd({ product: makeProduct(), locale: "en", url: "https://x" });
    expect(jsonLd.offers.availability).toBe("https://schema.org/InStock");
  });

  it("reports OutOfStock when every variant is out of stock", () => {
    const product = makeProduct({
      variants: [{ ...makeProduct().variants[0], available_quantity: 0 }],
    });
    const jsonLd = buildProductJsonLd({ product, locale: "en", url: "https://x" });
    expect(jsonLd.offers.availability).toBe("https://schema.org/OutOfStock");
  });

  it("omits aggregateRating when the product has no reviews yet", () => {
    const jsonLd = buildProductJsonLd({ product: makeProduct(), locale: "en", url: "https://x" });
    expect(jsonLd).not.toHaveProperty("aggregateRating");
  });

  it("includes aggregateRating once the product has reviews", () => {
    const product = makeProduct({ avg_rating: 4.5, review_count: 12 });
    const jsonLd = buildProductJsonLd({ product, locale: "en", url: "https://x" });
    expect(jsonLd.aggregateRating).toEqual({ "@type": "AggregateRating", ratingValue: 4.5, reviewCount: 12 });
  });

  it("renders the localized name for the requested locale", () => {
    const jsonLd = buildProductJsonLd({ product: makeProduct(), locale: "ar", url: "https://x" });
    expect(jsonLd.name).toBe("شابة التوقيعي");
  });
});

describe("canonicalUrl", () => {
  it("prefixes the locale onto the path under SITE_URL", () => {
    expect(canonicalUrl("/products/chaba-signature", "en")).toBe(
      "http://localhost:3000/en/products/chaba-signature",
    );
  });
});

describe("buildOrganizationJsonLd", () => {
  it("uses only real footer social links, never invented ones", () => {
    const footer = {
      socialLinks: [
        { platform: "instagram", url: "https://instagram.com/chaba" },
        { platform: "facebook", url: "https://facebook.com/chaba" },
      ],
    } as FooterData;

    const jsonLd = buildOrganizationJsonLd(footer);

    expect(jsonLd["@type"]).toBe("Organization");
    expect(jsonLd.name).toBe("CHABA");
    expect(jsonLd.sameAs).toEqual(["https://instagram.com/chaba", "https://facebook.com/chaba"]);
  });

  it("omits sameAs entirely when there are no social links (backend outage or none configured)", () => {
    const jsonLd = buildOrganizationJsonLd(null);
    expect(jsonLd).not.toHaveProperty("sameAs");
  });
});

describe("buildWebSiteJsonLd", () => {
  it("points the SearchAction at the locale's search page", () => {
    const jsonLd = buildWebSiteJsonLd("fr");
    expect(jsonLd.potentialAction.target).toBe("http://localhost:3000/fr/search?q={search_term_string}");
  });
});

describe("buildBreadcrumbJsonLd", () => {
  it("mirrors the visible crumb order and skips item for the current (href-less) page", () => {
    const jsonLd = buildBreadcrumbJsonLd(
      [{ label: "Home", href: "/" }, { label: "Perfumes", href: "/category/perfumes" }, { label: "Mavro" }],
      "en",
    );

    expect(jsonLd.itemListElement).toHaveLength(3);
    expect(jsonLd.itemListElement[0]).toEqual({
      "@type": "ListItem",
      position: 1,
      name: "Home",
      item: "http://localhost:3000/en/",
    });
    expect(jsonLd.itemListElement[2]).toEqual({ "@type": "ListItem", position: 3, name: "Mavro" });
  });
});

describe("buildFaqJsonLd", () => {
  it("maps each Q&A pair to a schema.org Question/Answer", () => {
    const jsonLd = buildFaqJsonLd([{ q: "Is it authentic?", a: "Yes, 100% original." }]);
    expect(jsonLd.mainEntity[0]).toEqual({
      "@type": "Question",
      name: "Is it authentic?",
      acceptedAnswer: { "@type": "Answer", text: "Yes, 100% original." },
    });
  });
});

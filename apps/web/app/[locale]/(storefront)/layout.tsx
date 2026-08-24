import { Suspense } from "react";
import { getLocale } from "next-intl/server";
import Script from "next/script";
import { CartProvider } from "@/components/cart/CartProvider";
import { CartDrawerProvider } from "@/components/cart/CartDrawerProvider";
import { CartDrawer } from "@/components/cart/CartDrawer";
import { WishlistProvider } from "@/components/wishlist/WishlistProvider";
import { CustomerAuthProvider } from "@/components/customer/CustomerAuthProvider";
import { ToastProvider } from "@/components/ui/Toast";
import { SiteHeader } from "@/components/layout/SiteHeader";
import { SiteFooter } from "@/components/layout/SiteFooter";
import { WhatsAppButton } from "@/components/layout/WhatsAppButton";
import { ShabaLoaderOverlay } from "@/components/ui/ShabaLoaderOverlay";
import { getFooterData, getCategories, type Category, type FooterData } from "@/lib/api";
import { buildOrganizationJsonLd, buildWebSiteJsonLd } from "@/lib/seo";

// Empty/unset until a real GA4 property ID is provided (see
// .env.local.example) — no placeholder ID is ever shipped, so this loads
// nothing until the user supplies theirs. Storefront-only (not admin), so
// staff back-office activity never pollutes customer analytics.
const GA_MEASUREMENT_ID = process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID;

async function loadFooterData(): Promise<FooterData | null> {
  try {
    return await getFooterData();
  } catch {
    // Backend outage: SiteFooter/WhatsAppButton both fall back to a minimal
    // rendering instead of the page crashing.
    return null;
  }
}

async function loadNavCategories(): Promise<Category[]> {
  try {
    return await getCategories();
  } catch {
    // Same reasoning as loadFooterData — a backend hiccup shows a header
    // with just the home link rather than crashing the whole layout.
    return [];
  }
}

export default async function StorefrontLayout({ children }: { children: React.ReactNode }) {
  // Fetched once here and passed down — the whole footer + the WhatsApp
  // button share this single request rather than each fetching separately.
  // categories: real, active catalog categories, not a hardcoded list — see
  // SiteHeader's own comment on why that distinction matters.
  const [footer, categories, locale] = await Promise.all([loadFooterData(), loadNavCategories(), getLocale()]);
  const organizationJsonLd = buildOrganizationJsonLd(footer);
  const websiteJsonLd = buildWebSiteJsonLd(locale);

  return (
    <CustomerAuthProvider>
      <CartProvider>
        <WishlistProvider>
          <CartDrawerProvider>
            <ToastProvider>
              <script
                type="application/ld+json"
                dangerouslySetInnerHTML={{ __html: JSON.stringify(organizationJsonLd).replace(/</g, "\\u003c") }}
              />
              <script
                type="application/ld+json"
                dangerouslySetInnerHTML={{ __html: JSON.stringify(websiteJsonLd).replace(/</g, "\\u003c") }}
              />
              <SiteHeader categories={categories} />
              <main className="flex-1">{children}</main>
              <SiteFooter footer={footer} />
              <CartDrawer />
              <WhatsAppButton whatsapp={footer?.settings.whatsapp} />
              {/* useSearchParams() inside ShabaLoaderOverlay requires a
                  Suspense boundary for statically-prerendered pages (about,
                  faq, terms, ...) or the production build fails — fallback
                  is null since the overlay itself renders nothing visible
                  until a navigation is actually pending. */}
              <Suspense fallback={null}>
                <ShabaLoaderOverlay />
              </Suspense>
              {GA_MEASUREMENT_ID && (
                <>
                  <Script src={`https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`} strategy="afterInteractive" />
                  <Script id="ga4-init" strategy="afterInteractive">
                    {`window.dataLayer = window.dataLayer || [];
                      function gtag(){dataLayer.push(arguments);}
                      gtag('js', new Date());
                      gtag('config', '${GA_MEASUREMENT_ID}');`}
                  </Script>
                </>
              )}
            </ToastProvider>
          </CartDrawerProvider>
        </WishlistProvider>
      </CartProvider>
    </CustomerAuthProvider>
  );
}

import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/seo";

// Cart/checkout/account/auth pages are client components (interactive
// forms, session-dependent state) — Next's generateMetadata only works in
// Server Components, so a per-page `robots: noindex` isn't available there.
// A sitewide disallow list (matched across every locale prefix) is the
// standard, reliable way to keep this transactional/user-specific content
// out of search results instead. The admin panel is excluded outright.
//
// /*/search is deliberately NOT in this list, unlike before: it's a real
// Server Component and now sets its own `robots: {index:false}` (see
// search/page.tsx's generateMetadata) — a crawl-block here would stop
// Google from ever reaching the page to see that directive, which is
// self-defeating for the one route on this list that actually can carry a
// real noindex meta tag.
export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      disallow: [
        "/*/cart",
        "/*/checkout",
        "/*/account",
        "/*/account/*",
        "/*/login",
        "/*/register",
        "/*/forgot-password",
        "/*/reset-password",
        "/*/orders",
        "/*/orders/*",
        "/*/notifications",
        "/*/track-order",
        "/*/wishlist",
        "/*/adminportal",
        "/*/adminportal/*",
      ],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}

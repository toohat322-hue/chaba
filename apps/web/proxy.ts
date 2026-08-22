import createMiddleware from "next-intl/middleware";
import { NextRequest, NextResponse } from "next/server";
import { routing } from "./i18n/routing";

const intlMiddleware = createMiddleware(routing);

// Server-only (no NEXT_PUBLIC_ prefix — never shipped to the browser bundle,
// only read here in middleware). Unset in an environment that hasn't
// adopted the admin subdomain yet, in which case this whole file behaves
// exactly as it did before (admin stays reachable at /admin on the main
// host, same as every other route).
const ADMIN_HOST = process.env.ADMIN_HOST;

function isAdminPath(pathname: string): boolean {
  if (pathname === "/admin" || pathname.startsWith("/admin/")) return true;

  return routing.locales.some((locale) => pathname === `/${locale}/admin` || pathname.startsWith(`/${locale}/admin/`));
}

/**
 * Separates the admin panel onto its own host (ADMIN_HOST, e.g.
 * `admin.chaba.dz` in production / `admin.localhost:3000` in dev) rather
 * than sharing the storefront's domain at /admin — a distinct, bookmarkable
 * "portal" URL, and one less thing a storefront visitor's browser history/
 * autocomplete ever surfaces. The actual page tree under app/[locale]/admin
 * is untouched: every admin link in the app is still a plain "/admin/..."
 * path (see AdminShell.tsx and co.), so this only ever needs to swap which
 * *host* those paths are reachable on, never rewrite the paths themselves.
 */
export default function proxy(request: NextRequest) {
  if (ADMIN_HOST) {
    const host = request.headers.get("host") ?? "";
    const { pathname } = request.nextUrl;

    if (host === ADMIN_HOST && !isAdminPath(pathname)) {
      // Bare root, or (unlikely) a storefront path someone typed directly —
      // this host only ever serves the admin app, so land there regardless.
      // /admin (no locale) redirects again through the branch below into
      // intlMiddleware, which adds the default locale prefix, same as any
      // other unprefixed path.
      const url = request.nextUrl.clone();
      url.pathname = "/admin";
      return NextResponse.redirect(url);
    }

    if (host !== ADMIN_HOST && isAdminPath(pathname)) {
      // An old chaba.dz/admin/... link/bookmark — send it to the portal
      // instead of serving admin off the storefront host going forward.
      const url = request.nextUrl.clone();
      url.host = ADMIN_HOST;
      return NextResponse.redirect(url);
    }
  }

  return intlMiddleware(request);
}

export const config = {
  matcher: ["/((?!api|trpc|_next|_vercel|.*\\..*).*)"],
};

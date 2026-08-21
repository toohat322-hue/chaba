import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin("./i18n/request.ts");

// Origin the browser needs to call directly (fetch/XHR) — the Laravel API.
// Falls back to the dev default so a missing env var doesn't silently strip
// the app's own backend from connect-src.
const apiOrigin = new URL(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000").origin;

// Product/hero-slide images are uploaded to a separate S3/MinIO origin
// (apps/api's AWS_ENDPOINT), not served from the API origin itself — img-src
// needs it explicitly, or every uploaded image is silently blocked by CSP
// rather than rendered (only caught once a real upload was tested live).
const assetUrl = process.env.NEXT_PUBLIC_ASSET_URL ? new URL(process.env.NEXT_PUBLIC_ASSET_URL) : null;
const assetOrigin = assetUrl?.origin ?? null;

// Static (non-nonce) CSP: safe baseline against externally-injected scripts/
// frames/exfil origins without risking a nonce-based policy breaking
// Next.js's own inline hydration payloads. img-src stays broad (https: +
// the API/asset origins' http in dev).
// Next.js dev mode (both webpack and Turbopack) relies on eval() for HMR
// module wrapping/sourcemaps — only relaxed outside production so the
// shipped policy stays as tight as the static-header approach allows.
const baseScriptSrc = process.env.NODE_ENV === "production" ? "'self' 'unsafe-inline'" : "'self' 'unsafe-inline' 'unsafe-eval'";

// GA4 is off (no script tag rendered at all) until a real measurement ID is
// set — see the storefront layout — so these origins are only added to the
// CSP once GA4 is actually enabled, keeping the default policy as tight as
// before for every deployment that hasn't configured analytics.
const gaEnabled = Boolean(process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID);
const scriptSrc = gaEnabled ? `${baseScriptSrc} https://www.googletagmanager.com` : baseScriptSrc;
const connectSrcExtra = gaEnabled ? " https://www.google-analytics.com https://analytics.google.com" : "";

const csp = [
  "default-src 'self'",
  `script-src ${scriptSrc}`,
  "style-src 'self' 'unsafe-inline'",
  // blob: is required for local image previews (product-image upload
  // dropzone) via URL.createObjectURL — those never leave the browser, so
  // this doesn't widen what the CSP actually protects against.
  `img-src 'self' data: blob: https: ${apiOrigin}${assetOrigin ? ` ${assetOrigin}` : ""}`,
  "font-src 'self' data:",
  `connect-src 'self' ${apiOrigin}${connectSrcExtra}`,
  "frame-ancestors 'none'",
  "base-uri 'self'",
  "form-action 'self'",
].join("; ");

const securityHeaders = [
  { key: "Content-Security-Policy", value: csp },
  { key: "Strict-Transport-Security", value: "max-age=63072000; includeSubDomains" },
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "X-Frame-Options", value: "DENY" },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
];

const nextConfig: NextConfig = {
  images: {
    // Product/hero-slide images live on a separate S3/MinIO origin (same
    // env var already trusted for the CSP img-src directive above) — this
    // is what makes next/image (used by ProductGallery/ProductCard for
    // responsive srcset + format negotiation) able to optimize them.
    remotePatterns: assetUrl
      ? [
          {
            protocol: assetUrl.protocol === "https:" ? "https" : "http",
            hostname: assetUrl.hostname,
            port: assetUrl.port,
          },
        ]
      : [],
    // Dev-only: local MinIO is reached at a loopback address
    // (NEXT_PUBLIC_ASSET_URL=http://127.0.0.1:9000), which Next's image
    // optimizer refuses by default as an SSRF precaution. That protection
    // exists for arbitrary/user-controlled hosts; here the origin is locked
    // down to the one exact host above via remotePatterns, not user input,
    // so it's safe to relax — and only ever in dev, since production uses a
    // real public S3/CDN domain, never a private IP.
    ...(process.env.NODE_ENV !== "production" && { dangerouslyAllowLocalIP: true }),
  },
  async headers() {
    return [
      { source: "/:path*", headers: securityHeaders },
      // Brand assets (logo, hero image) are static, versioned by filename
      // change, not URL change — safe to cache for a full year.
      {
        source: "/brand/:path*",
        headers: [{ key: "Cache-Control", value: "public, max-age=31536000, immutable" }],
      },
    ];
  },
};

export default withNextIntl(nextConfig);

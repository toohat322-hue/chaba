import Image from "next/image";

type ShabaLoaderProps = {
  /**
   * `true` for a fixed full-viewport overlay (route-level transitions —
   * nothing else is on screen yet). `false` (default) renders in normal
   * flow, sized by the caller, for a client page/drawer swapping its own
   * content area out while a fetch is in flight — header/footer/layout
   * chrome around it stays mounted.
   */
  full?: boolean;
  /** Accessible status text — pass the caller's own translated string
   * (this component has no i18n/context dependency of its own so it can be
   * rendered from both Server Components like loading.tsx and Client
   * Components alike). Defaults to English since a missing label shouldn't
   * ever break rendering. */
  label?: string;
  /** Visible text under the logo, without a trailing ellipsis — the three
   * dots are animated separately (see .shaba-loader-dots). Falls back to
   * `label` with any trailing dots stripped, so callers that opt into
   * `showIndicator` don't also need to pass this. */
  labelBase?: string;
  /** Adds the visible dot-indicator row and a light shimmer sweep over the
   * logo — the "this is the main, deliberate loading moment" treatment.
   * Independent of `full`: ShabaLoaderOverlay renders `full={false}` (its
   * own `.shaba-loader-overlay` wrapper already provides the fixed
   * full-viewport chrome) but still wants this richer decoration, while a
   * small inline loader (account pages, cart drawer, ...) stays icon-only.
   */
  showIndicator?: boolean;
  className?: string;
};

// Real CHABA logo, no invented mark — same asset SiteHeader/SiteFooter
// already render via next/image. The 150ms animation-delay (see
// .animate-shaba-loader in globals.css) is the anti-flicker mechanism: the
// element exists immediately (no later layout shift) but stays invisible
// until 150ms in, so a load that resolves faster than that never visibly
// flashes the loader at all.
export function ShabaLoader({
  full = false,
  label = "Loading...",
  labelBase,
  showIndicator = false,
  className = "",
}: ShabaLoaderProps) {
  const logoSize = full ? 96 : 64;
  // Strips a trailing "..."/"…" from `label` so full-mode callers that
  // haven't been updated to pass labelBase explicitly still get clean text
  // next to the separately-animated dots, instead of "Loading...⋯".
  const visibleLabel = labelBase ?? label.replace(/[.…]+$/, "");

  return (
    <div
      role="status"
      aria-live="polite"
      className={`flex flex-col items-center justify-center gap-4 ${full ? "fixed inset-0 z-50 bg-background" : "min-h-40 w-full py-12"} ${className}`}
    >
      <div className="relative flex items-center justify-center" style={{ width: logoSize, height: logoSize }}>
        <span
          aria-hidden="true"
          className="animate-shaba-loader-glow absolute rounded-full bg-accent/40 blur-2xl"
          style={{ width: logoSize * 1.6, height: logoSize * 1.6 }}
        />
        <Image
          src="/brand/logo.png"
          alt=""
          width={logoSize}
          height={logoSize}
          priority={full}
          className="animate-shaba-loader relative"
        />
        {/* A light diagonal sweep masked to the logo's own silhouette, not a
            rectangle over it. Purely decorative (aria-hidden) and skipped
            when reduced-motion applies (see globals.css). */}
        {showIndicator && (
          <span
            aria-hidden="true"
            className="shaba-loader-shimmer absolute inset-0"
            style={{ maskImage: "url(/brand/logo.png)", WebkitMaskImage: "url(/brand/logo.png)" }}
          />
        )}
      </div>

      {showIndicator && (
        <p aria-hidden="true" className="flex items-center gap-1.5 text-caption text-ink/50">
          <span>{visibleLabel}</span>
          <span className="shaba-loader-dots">
            <span className="shaba-loader-dot" />
            <span className="shaba-loader-dot" />
            <span className="shaba-loader-dot" />
          </span>
        </p>
      )}

      <span className="sr-only">{label}</span>
    </div>
  );
}

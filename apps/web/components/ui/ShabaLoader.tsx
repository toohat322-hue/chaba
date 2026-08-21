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
  className?: string;
};

// Real CHABA logo, no invented mark — same asset SiteHeader/SiteFooter
// already render via next/image. The 150ms animation-delay (see
// .animate-shaba-loader in globals.css) is the anti-flicker mechanism: the
// element exists immediately (no later layout shift) but stays invisible
// until 150ms in, so a load that resolves faster than that never visibly
// flashes the loader at all.
export function ShabaLoader({ full = false, label = "Loading...", className = "" }: ShabaLoaderProps) {
  const logoSize = full ? 96 : 64;

  return (
    <div
      role="status"
      aria-live="polite"
      className={`flex items-center justify-center ${full ? "fixed inset-0 z-50 bg-background" : "min-h-40 w-full py-12"} ${className}`}
    >
      <div className="relative flex items-center justify-center">
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
      </div>
      <span className="sr-only">{label}</span>
    </div>
  );
}

import Image from "next/image";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import type { HeroSlide } from "@/lib/api";
import { useImageRetry } from "@/lib/useImageRetry";

type Locale = "ar" | "fr" | "en";

/**
 * The visual content of a single hero slide — image(s), scrim, headline,
 * subtitle, CTA. Shared by the live HeroSlider (interactive, autoplaying)
 * and the admin's HeroSlidePreview (static, non-interactive) so the two
 * never drift out of sync with each other.
 */
export function HeroSlideVisual({
  slide,
  locale,
  eager = false,
  interactive = true,
  animate = true,
}: {
  slide: HeroSlide;
  locale: Locale;
  /** LCP-critical first slide: skip lazy-loading, use fetchPriority high. */
  eager?: boolean;
  /** false for the admin preview — renders a plain div instead of a Link. */
  interactive?: boolean;
  /** false for the admin preview — skips the zoom/content-entrance animation. */
  animate?: boolean;
}) {
  const tHero = useTranslations("Hero");
  const href = `/products/${slide.product.slug}`;
  const title = slide.title[locale];
  const subtitle = slide.subtitle[locale];
  const ctaLabel = slide.cta_label[locale];
  const image = useImageRetry();

  const content = (
    <>
      {/* A couple of quick retries recover from a transient load failure
          without the visitor needing to refresh the page; if it's still
          broken after that, the section's own bg-primary + scrim already
          look intentional on their own — headline/CTA stay fully readable
          either way, so there's nothing else to fall back to here. */}
      {!image.failed && (
        <picture key={image.key}>
          {slide.mobile_image_url && <source media="(max-width: 640px)" srcSet={slide.mobile_image_url} />}
          <img
            src={slide.image_url ?? "/brand/hero.jpg"}
            alt=""
            loading={eager ? "eager" : "lazy"}
            fetchPriority={eager ? "high" : "auto"}
            onError={image.onError}
            className={`absolute inset-0 h-full w-full object-cover object-center ${animate ? "animate-hero-zoom" : ""}`}
          />
        </picture>
      )}

      <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-primary/70 via-primary/20 to-transparent" />

      <div className="relative z-10 mx-auto flex h-full w-full max-w-2xl flex-col items-center justify-end gap-5 px-6 pb-16 text-center text-background sm:pb-20">
        <div className={`flex flex-col gap-2.5 ${animate ? "animate-hero-slide-content" : ""}`}>
          <h1 className="text-h1 font-medium leading-snug tracking-wide drop-shadow-sm sm:text-display">{title}</h1>
          {subtitle && <p className="text-body font-light text-background/80 sm:text-h3">{subtitle}</p>}
        </div>

        <span
          className={`mt-2 inline-flex h-12 items-center justify-center rounded-md border border-primary/15 bg-accent px-7 text-body font-medium text-primary shadow-soft transition-all duration-300 ease-out ${
            interactive ? "hover:-translate-y-0.5 hover:bg-accent/90" : ""
          } ${animate ? "animate-hero-slide-content" : ""}`}
          style={animate ? { animationDelay: "150ms" } : undefined}
        >
          {ctaLabel}
        </span>
      </div>
    </>
  );

  if (!interactive) {
    return <div className="relative isolate h-full w-full overflow-hidden bg-primary">{content}</div>;
  }

  return (
    <Link
      href={href}
      aria-label={tHero("heroProductAriaLabel")}
      className="relative isolate block h-full w-full overflow-hidden bg-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-accent/60"
    >
      {content}
    </Link>
  );
}

/** Re-exported so the Link-wrapped brand logo above the slider matches the old static hero exactly. */
export function HeroLogo({ alt }: { alt: string }) {
  return (
    <Image
      src="/brand/logo.png"
      alt={alt}
      width={64}
      height={64}
      priority
      className="h-14 w-14 rounded-xl shadow-lift sm:h-16 sm:w-16"
    />
  );
}

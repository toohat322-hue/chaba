"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";
import { rtlLocales } from "@/i18n/routing";
import { PerfumeGlyph } from "./PerfumeGlyph";

type GalleryImage = { url: string; alt_text: string | null };
type Locale = "ar" | "fr" | "en";

const AUTOPLAY_MS = 5000;
const SWIPE_THRESHOLD_PX = 50;
// Same bounded-retry behavior as useImageRetry (lib/useImageRetry.ts) — not
// reused directly since it tracks one image, and this gallery needs a
// separate attempt count per index.
const MAX_IMAGE_RETRIES = 2;

function ChevronLeft({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
      <path d="M15 6l-6 6 6 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function ChevronRight({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
      <path d="M9 6l6 6-6 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export function ProductGallery({ images, title }: { images: GalleryImage[]; title: string }) {
  const t = useTranslations("Product");
  const locale = useLocale() as Locale;
  const isRtl = rtlLocales.has(locale);

  const [activeIndex, setActiveIndex] = useState(0);
  const [isHovering, setIsHovering] = useState(false);
  // Per-image, not gallery-wide — one bad URL falls back to the placeholder
  // glyph for just that slide; the other real photos and the dots/arrows
  // keep working normally. A couple of retries first (a transient load
  // failure recovers on its own rather than needing a page refresh); only
  // a still-broken image after that gets the permanent fallback.
  const [failedIndexes, setFailedIndexes] = useState<ReadonlySet<number>>(new Set());
  const [retryAttempts, setRetryAttempts] = useState<Readonly<Record<number, number>>>({});
  const touchStartX = useRef<number | null>(null);

  const count = images.length;

  const goTo = useCallback((index: number) => setActiveIndex(((index % count) + count) % count), [count]);
  const next = useCallback(() => goTo(activeIndex + 1), [activeIndex, goTo]);
  const prev = useCallback(() => goTo(activeIndex - 1), [activeIndex, goTo]);

  useEffect(() => {
    if (isHovering || count <= 1) return;
    const timer = setInterval(() => setActiveIndex((i) => (i + 1) % count), AUTOPLAY_MS);
    return () => clearInterval(timer);
  }, [isHovering, count]);

  function handleTouchStart(event: React.TouchEvent) {
    touchStartX.current = event.touches[0].clientX;
  }

  function handleTouchEnd(event: React.TouchEvent) {
    if (touchStartX.current === null) return;
    const deltaX = event.changedTouches[0].clientX - touchStartX.current;
    touchStartX.current = null;
    if (Math.abs(deltaX) < SWIPE_THRESHOLD_PX) return;

    // A right-to-left drag (deltaX < 0) advances forward in LTR reading
    // order but goes backward in RTL — swipe direction is mirrored, not
    // just the arrow icons (same logic as HeroSlider).
    const draggedLeft = deltaX < 0;
    if (draggedLeft === !isRtl) next();
    else prev();
  }

  function handleImageError(index: number) {
    setRetryAttempts((prev) => {
      const attempts = prev[index] ?? 0;
      if (attempts >= MAX_IMAGE_RETRIES) {
        setFailedIndexes((failed) => new Set(failed).add(index));
        return prev;
      }
      return { ...prev, [index]: attempts + 1 };
    });
  }

  function handleKeyDown(event: React.KeyboardEvent) {
    if (count <= 1) return;
    if (event.key === "ArrowLeft") {
      if (isRtl) next();
      else prev();
    } else if (event.key === "ArrowRight") {
      if (isRtl) prev();
      else next();
    }
  }

  if (count === 0) {
    return (
      <div className="aspect-square overflow-hidden rounded-2xl shadow-soft">
        <PerfumeGlyph className="h-full w-full" />
      </div>
    );
  }

  return (
    <div
      data-testid="gallery-main-image"
      tabIndex={count > 1 ? 0 : -1}
      className="relative aspect-square overflow-hidden rounded-2xl bg-white shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
      onKeyDown={handleKeyDown}
    >
      {/* All images mount at once and cross-fade via opacity (not swapped
          in/out per activeIndex) so autoplay never shows a fresh network
          fetch/pop-in — matches the Hero Slider's established pattern. */}
      {images.map((image, i) => (
        <div
          key={image.url}
          aria-hidden={i !== activeIndex}
          className={`absolute inset-0 transition-opacity duration-700 ease-out ${
            i === activeIndex ? "z-10 opacity-100" : "pointer-events-none z-0 opacity-0"
          }`}
        >
          {failedIndexes.has(i) ? (
            <PerfumeGlyph className="h-full w-full" />
          ) : (
            <Image
              key={retryAttempts[i] ?? 0}
              src={image.url}
              alt={image.alt_text ?? title}
              fill
              sizes="(min-width: 768px) 50vw, 100vw"
              priority={i === 0}
              className="object-cover"
              onError={() => handleImageError(i)}
            />
          )}
        </div>
      ))}

      {count > 1 && (
        <>
          <button
            type="button"
            onClick={prev}
            aria-label={t("prevImage")}
            className="absolute inset-y-0 start-2 z-20 my-auto flex h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow-soft backdrop-blur transition-colors hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
          >
            {isRtl ? <ChevronRight className="h-5 w-5" /> : <ChevronLeft className="h-5 w-5" />}
          </button>
          <button
            type="button"
            onClick={next}
            aria-label={t("nextImage")}
            className="absolute inset-y-0 end-2 z-20 my-auto flex h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow-soft backdrop-blur transition-colors hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
          >
            {isRtl ? <ChevronLeft className="h-5 w-5" /> : <ChevronRight className="h-5 w-5" />}
          </button>

          <div className="absolute bottom-3 start-1/2 z-20 flex -translate-x-1/2 gap-2">
            {images.map((image, i) => (
              <button
                key={image.url}
                type="button"
                onClick={() => goTo(i)}
                aria-label={t("goToImage", { number: i + 1 })}
                aria-current={i === activeIndex}
                className={`h-2 w-2 rounded-full border transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 ${
                  i === activeIndex ? "border-accent bg-accent" : "border-ink/30 bg-white/70 hover:border-ink/50"
                }`}
              />
            ))}
          </div>
        </>
      )}
    </div>
  );
}

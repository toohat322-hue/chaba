"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import type { HeroSlide } from "@/lib/api";
import { rtlLocales } from "@/i18n/routing";
import { HeroSlideVisual, HeroLogo } from "./HeroSlideVisual";

type Locale = "ar" | "fr" | "en";

const AUTOPLAY_MS = 5000;
const SWIPE_THRESHOLD_PX = 50;

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

function PauseIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <rect x="6" y="5" width="4" height="14" rx="1" />
      <rect x="14" y="5" width="4" height="14" rx="1" />
    </svg>
  );
}

function PlayIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <path d="M8 5v14l11-7z" />
    </svg>
  );
}

export function HeroSlider({ slides }: { slides: HeroSlide[] }) {
  const tHome = useTranslations("Home");
  const tHero = useTranslations("Hero");
  const locale = useLocale() as Locale;
  const isRtl = rtlLocales.has(locale);

  const [activeIndex, setActiveIndex] = useState(0);
  const [isHovering, setIsHovering] = useState(false);
  const [isManuallyPaused, setIsManuallyPaused] = useState(false);
  const touchStartX = useRef<number | null>(null);

  const count = slides.length;

  const goTo = useCallback((index: number) => setActiveIndex(((index % count) + count) % count), [count]);
  const next = useCallback(() => goTo(activeIndex + 1), [activeIndex, goTo]);
  const prev = useCallback(() => goTo(activeIndex - 1), [activeIndex, goTo]);

  const paused = isHovering || isManuallyPaused;

  useEffect(() => {
    if (paused || count <= 1) return;
    const timer = setInterval(() => setActiveIndex((i) => (i + 1) % count), AUTOPLAY_MS);
    return () => clearInterval(timer);
  }, [paused, count]);

  function handleTouchStart(event: React.TouchEvent) {
    touchStartX.current = event.touches[0].clientX;
    setIsHovering(true);
  }

  function handleTouchEnd(event: React.TouchEvent) {
    setIsHovering(false);
    if (touchStartX.current === null) return;

    const deltaX = event.changedTouches[0].clientX - touchStartX.current;
    touchStartX.current = null;
    if (Math.abs(deltaX) < SWIPE_THRESHOLD_PX) return;

    // A right-to-left drag (deltaX < 0) advances forward in LTR reading
    // order but goes backward in RTL — swipe direction is mirrored, not
    // just the arrow icons.
    const draggedLeft = deltaX < 0;
    if (draggedLeft === !isRtl) next();
    else prev();
  }

  return (
    <section
      className="relative isolate flex min-h-[75vh] items-end overflow-hidden bg-primary lg:min-h-[720px]"
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {/* All slides mount at once (not swapped in/out per activeIndex) so
          the opacity transition actually has two images to cross-dissolve
          between, and so switching slides never shows a fresh network
          fetch/pop-in — only visibility (opacity) changes on advance. */}
      {slides.map((slide, index) => (
        <div
          key={slide.id}
          aria-hidden={index !== activeIndex}
          className={`absolute inset-0 transition-opacity duration-700 ease-out ${
            index === activeIndex ? "z-10 opacity-100" : "pointer-events-none z-0 opacity-0"
          }`}
        >
          <HeroSlideVisual slide={slide} locale={locale} eager={index === 0} />
        </div>
      ))}

      {/* Brand mark stays constant above the crossfading slide content —
          identity shouldn't flicker with every product rotation. */}
      <div className="pointer-events-none absolute inset-x-0 top-10 z-20 flex justify-center">
        <HeroLogo alt={tHome("title")} />
      </div>

      {count > 1 && (
        <>
          <button
            type="button"
            onClick={prev}
            aria-label={tHero("prevSlide")}
            className="absolute inset-y-0 start-2 z-20 my-auto flex h-10 w-10 items-center justify-center rounded-full bg-background/10 text-background backdrop-blur transition-colors hover:bg-background/20 sm:start-4"
          >
            {isRtl ? <ChevronRight className="h-5 w-5" /> : <ChevronLeft className="h-5 w-5" />}
          </button>
          <button
            type="button"
            onClick={next}
            aria-label={tHero("nextSlide")}
            className="absolute inset-y-0 end-2 z-20 my-auto flex h-10 w-10 items-center justify-center rounded-full bg-background/10 text-background backdrop-blur transition-colors hover:bg-background/20 sm:end-4"
          >
            {isRtl ? <ChevronLeft className="h-5 w-5" /> : <ChevronRight className="h-5 w-5" />}
          </button>

          <div className="absolute bottom-4 start-1/2 z-20 flex -translate-x-1/2 items-center gap-2.5">
            {slides.map((slide, index) => (
              <button
                key={slide.id}
                type="button"
                onClick={() => goTo(index)}
                aria-label={tHero("goToSlide", { number: index + 1 })}
                aria-current={index === activeIndex}
                className={`h-2 rounded-full transition-all ${
                  index === activeIndex ? "w-6 bg-accent" : "w-2 bg-background/50 hover:bg-background/70"
                }`}
              />
            ))}
            <button
              type="button"
              onClick={() => setIsManuallyPaused((p) => !p)}
              aria-label={isManuallyPaused ? tHero("playSlideshow") : tHero("pauseSlideshow")}
              className="ms-1 flex h-6 w-6 items-center justify-center rounded-full text-background/70 transition-colors hover:text-background"
            >
              {isManuallyPaused ? <PlayIcon className="h-3.5 w-3.5" /> : <PauseIcon className="h-3.5 w-3.5" />}
            </button>
          </div>
        </>
      )}
    </section>
  );
}

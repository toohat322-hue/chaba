"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import { PerfumeGlyph } from "./PerfumeGlyph";

type GalleryImage = { url: string; alt_text: string | null };

const AUTOPLAY_MS = 5000;

export function ProductGallery({ images, title }: { images: GalleryImage[]; title: string }) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [isHovering, setIsHovering] = useState(false);

  const count = images.length;

  useEffect(() => {
    if (isHovering || count <= 1) return;
    const timer = setInterval(() => setActiveIndex((i) => (i + 1) % count), AUTOPLAY_MS);
    return () => clearInterval(timer);
  }, [isHovering, count]);

  if (count === 0) {
    return (
      <div className="aspect-square overflow-hidden rounded-2xl shadow-soft">
        <PerfumeGlyph className="h-full w-full" />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <div
        data-testid="gallery-main-image"
        className="relative aspect-square overflow-hidden rounded-2xl bg-white shadow-soft"
        onMouseEnter={() => setIsHovering(true)}
        onMouseLeave={() => setIsHovering(false)}
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
            <Image
              src={image.url}
              alt={image.alt_text ?? title}
              fill
              sizes="(min-width: 768px) 50vw, 100vw"
              priority={i === 0}
              className="object-cover"
            />
          </div>
        ))}
      </div>
      {count > 1 && (
        <div className="flex gap-3 overflow-x-auto pb-1">
          {images.map((image, i) => (
            <button
              key={image.url}
              type="button"
              onClick={() => setActiveIndex(i)}
              aria-label={`${title} ${i + 1}`}
              aria-current={i === activeIndex}
              className={`h-20 w-20 shrink-0 overflow-hidden rounded-lg border-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 ${
                i === activeIndex ? "border-accent" : "border-transparent hover:border-primary/20"
              }`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element -- tiny 80px thumbnail, next/image's overhead isn't worth it here */}
              <img src={image.url} alt={image.alt_text ?? title} className="h-full w-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

"use client";

import { useImageRetry } from "@/lib/useImageRetry";
import { PerfumeGlyph } from "@/components/product/PerfumeGlyph";

/**
 * The stateful (retry-on-error) half of a category tile's image — split out
 * from DiscoverCategories (a Server Component, which can't hold this state
 * itself) so the fetch/render of the category list stays server-side.
 */
export function CategoryTileImage({ imageUrl, alt, className }: { imageUrl: string; alt: string; className: string }) {
  const image = useImageRetry();

  if (image.failed) {
    return <PerfumeGlyph className={className} />;
  }

  return (
    // eslint-disable-next-line @next/next/no-img-element -- remote catalog image, retried on error
    <img key={image.key} src={imageUrl} alt={alt} loading="lazy" onError={image.onError} className={className} />
  );
}

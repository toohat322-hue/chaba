"use client";

import { useImageRetry } from "@/lib/useImageRetry";

/** One review photo thumbnail, with the shared retry-then-fallback behavior — split out since useImageRetry (a hook) can't be called inside the .map() that renders these. */
export function ReviewPhoto({ url, alt }: { url: string; alt: string }) {
  const image = useImageRetry();

  if (image.failed) {
    return null;
  }

  return (
    // eslint-disable-next-line @next/next/no-img-element -- remote catalog image, retried on error
    <img key={image.key} src={url} alt={alt} onError={image.onError} className="h-16 w-16 rounded-lg object-cover" />
  );
}

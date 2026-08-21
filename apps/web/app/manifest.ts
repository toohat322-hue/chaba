import type { MetadataRoute } from "next";

// Next's native manifest route convention (same pattern as sitemap.ts/
// robots.ts in this directory) — auto-served at /manifest.webmanifest and
// auto-linked into every page's <head>, no public/manifest.json needed.
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "CHABA",
    short_name: "CHABA",
    description: "عطرك المفضل من قلب الجزائر",
    start_url: "/",
    display: "standalone",
    background_color: "#faf7f0",
    theme_color: "#0e3b36",
    icons: [
      {
        src: "/brand/logo.png",
        sizes: "2000x2000",
        type: "image/png",
      },
    ],
  };
}

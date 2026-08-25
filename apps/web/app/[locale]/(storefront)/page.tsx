import { Suspense } from "react";
import { HeroSection } from "@/components/home/HeroSection";
import { HeroSectionSkeleton } from "@/components/home/HeroSectionSkeleton";
import { TrustBar } from "@/components/home/TrustBar";
import { MarqueeStrip } from "@/components/home/MarqueeStrip";
import { DiscoverCategories } from "@/components/home/DiscoverCategories";
import { DiscoverCategoriesSkeleton } from "@/components/home/DiscoverCategoriesSkeleton";
import { BestSellersRail } from "@/components/home/BestSellersRail";
import { BestSellersRailSkeleton } from "@/components/home/BestSellersRailSkeleton";
import { languageAlternates, canonicalUrl } from "@/lib/seo";

// Catalog data (stock, prices, featured flags) must render fresh per
// request — this route is intentionally never statically prerendered.
export const dynamic = "force-dynamic";

type Props = { params: Promise<{ locale: string }> };

// Title/description/OG already come from the root layout's defaults (this
// *is* the site's default page) — only canonical/hreflang are genuinely
// page-specific.
export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  return { alternates: { canonical: canonicalUrl("/", locale), languages: languageAlternates("/") } };
}

export default function HomePage() {
  // Each data-dependent section gets its own Suspense boundary so a slow or
  // failing fetch (a cold-starting API, a flaky network) only ever delays
  // that one section — never the rest of the page, and never a page-wide
  // "Loading..." blocking everything behind it.
  return (
    <>
      <Suspense fallback={<HeroSectionSkeleton />}>
        <HeroSection />
      </Suspense>
      <TrustBar />
      <MarqueeStrip />
      <Suspense fallback={<DiscoverCategoriesSkeleton />}>
        <DiscoverCategories />
      </Suspense>
      <Suspense fallback={<BestSellersRailSkeleton />}>
        <BestSellersRail />
      </Suspense>
    </>
  );
}

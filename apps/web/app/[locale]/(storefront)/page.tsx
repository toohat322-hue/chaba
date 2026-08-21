import { Suspense } from "react";
import { HeroBanner } from "@/components/home/HeroBanner";
import { HeroSlider } from "@/components/home/HeroSlider";
import { TrustBar } from "@/components/home/TrustBar";
import { MarqueeStrip } from "@/components/home/MarqueeStrip";
import { DiscoverCategories } from "@/components/home/DiscoverCategories";
import { BestSellersRail } from "@/components/home/BestSellersRail";
import { BestSellersRailSkeleton } from "@/components/home/BestSellersRailSkeleton";
import { languageAlternates, canonicalUrl } from "@/lib/seo";
import { getHeroSlides } from "@/lib/api";

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

export default async function HomePage() {
  // Admin-managed hero carousel — a backend outage or zero configured
  // slides both degrade to the original static hero (HeroBanner) rather
  // than an empty section or a broken page.
  const slides = await getHeroSlides().catch(() => []);

  return (
    <>
      {slides.length > 0 ? <HeroSlider slides={slides} /> : <HeroBanner />}
      <TrustBar />
      <MarqueeStrip />
      <DiscoverCategories />
      <Suspense fallback={<BestSellersRailSkeleton />}>
        <BestSellersRail />
      </Suspense>
    </>
  );
}

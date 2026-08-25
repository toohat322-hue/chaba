import { HeroBanner } from "./HeroBanner";
import { HeroSlider } from "./HeroSlider";
import { getHeroSlides } from "@/lib/api";

/**
 * Isolates the hero-slides fetch behind its own Suspense boundary (see
 * page.tsx) so a slow/cold backend only ever delays the hero section, never
 * the rest of the homepage — TrustBar/MarqueeStrip/DiscoverCategories all
 * render regardless of how long this takes.
 */
export async function HeroSection() {
  // Admin-managed hero carousel — a backend outage or zero configured
  // slides both degrade to the original static hero (HeroBanner) rather
  // than an empty section or a broken page.
  const slides = await getHeroSlides().catch(() => []);

  return slides.length > 0 ? <HeroSlider slides={slides} /> : <HeroBanner />;
}

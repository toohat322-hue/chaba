import Image from "next/image";
import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { getFeaturedProducts } from "@/lib/api";

export async function HeroBanner() {
  const [tHome, tHero] = await Promise.all([getTranslations("Home"), getTranslations("Hero")]);

  // The hero photo doubles as a "shop the look" link to the store's current
  // flagship item — the most recently featured active product (admin-
  // controlled via the existing Featured toggle, ordered newest-first by
  // the API). No dedicated "hero product" field exists, so this is the most
  // faithful real signal of "the product the admin wants front and center"
  // without inventing new backend state. If nothing is featured, the image
  // simply isn't a link.
  const featured = await getFeaturedProducts().catch(() => []);
  const heroProduct = featured[0] ?? null;

  return (
    <section className="relative isolate flex min-h-[75vh] items-end overflow-hidden bg-primary lg:min-h-[720px]">
      {heroProduct ? (
        <Link
          href={`/products/${heroProduct.slug}`}
          aria-label={tHero("heroProductAriaLabel")}
          className="absolute inset-0 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-accent/60"
        >
          <Image
            src="/brand/hero.jpg"
            alt=""
            fill
            priority
            sizes="100vw"
            className="animate-hero-zoom object-cover object-center"
          />
        </Link>
      ) : (
        <div className="absolute inset-0">
          <Image
            src="/brand/hero.jpg"
            alt=""
            fill
            priority
            sizes="100vw"
            className="animate-hero-zoom object-cover object-center"
          />
        </div>
      )}
      {/* Soft, bottom-weighted scrim — just enough for text legibility without
          burying the bottle/water photography beneath it. pointer-events-none
          so it never blocks the hero-image link sitting beneath it. */}
      <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-primary/70 via-primary/20 to-transparent" />

      <div className="relative z-10 mx-auto flex w-full max-w-2xl flex-col items-center gap-5 px-6 pb-16 text-center text-background sm:pb-20">
        <Image
          src="/brand/logo.png"
          alt={tHome("title")}
          width={64}
          height={64}
          priority
          className="h-14 w-14 rounded-xl shadow-lift sm:h-16 sm:w-16"
        />

        <div className="flex flex-col gap-2.5">
          <h1 className="text-h1 font-medium leading-snug tracking-wide drop-shadow-sm sm:text-display">
            {tHero("headlineMain")}
          </h1>
          <p className="text-body font-light text-background/80 sm:text-h3">{tHero("headlineSub")}</p>
        </div>

        <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
          <Link
            href="/category/exclusive-offers"
            className="inline-flex h-12 items-center justify-center rounded-md border border-primary/15 bg-accent px-7 text-body font-medium text-primary shadow-soft transition-all duration-300 ease-out hover:-translate-y-0.5 hover:bg-accent/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-background/70 focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
          >
            {tHero("primaryCta")}
          </Link>
          <Link
            href="/#explore-perfumes"
            className="inline-flex h-12 items-center justify-center rounded-md border border-background/40 px-7 text-body font-medium text-background transition-all duration-300 ease-out hover:-translate-y-0.5 hover:border-background/70 hover:bg-background/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-background/70 focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
          >
            {tHero("secondaryCta")}
          </Link>
        </div>
      </div>
    </section>
  );
}

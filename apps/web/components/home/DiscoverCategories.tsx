import { getTranslations, getLocale } from "next-intl/server";
import { getCategories } from "@/lib/api";
import { Link } from "@/i18n/navigation";
import { PerfumeGlyph } from "@/components/product/PerfumeGlyph";

type Locale = "ar" | "fr" | "en";

export async function DiscoverCategories() {
  const [t, locale] = await Promise.all([getTranslations("Discover"), getLocale()]);
  const loc = locale as Locale;

  // No hardcoded category list — whatever's actually active in the catalog
  // is what renders here, so this section never drifts from real data.
  const categories = await getCategories().catch((error) => {
    console.error("Failed to load categories", error);
    return [];
  });

  if (categories.length === 0) {
    return null;
  }

  return (
    <section id="explore-perfumes" className="mx-auto max-w-7xl scroll-mt-20 px-6 py-16">
      <div className="text-center">
        <h2 className="text-h1 font-semibold text-primary">{t("heading")}</h2>
        <p className="mt-2 text-body text-ink/70">{t("subheading")}</p>
      </div>

      <div className="mt-10 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-4">
        {categories.map((category) => (
          <Link
            key={category.id}
            href={`/category/${category.slug}`}
            className="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-2xl shadow-soft transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lift focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          >
            {category.image_url ? (
              // eslint-disable-next-line @next/next/no-img-element -- remote catalog image
              <img
                src={category.image_url}
                alt={category.name[loc]}
                loading="lazy"
                className="absolute inset-0 h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
              />
            ) : (
              <PerfumeGlyph className="absolute inset-0 h-full w-full opacity-90 transition-transform duration-300 ease-out group-hover:scale-105" />
            )}
            <div className="absolute inset-0 bg-gradient-to-t from-primary/85 via-primary/10 to-transparent" />
            <span className="relative z-10 p-4 text-body font-semibold text-background sm:text-h3">
              {category.name[loc]}
            </span>
          </Link>
        ))}
      </div>
    </section>
  );
}

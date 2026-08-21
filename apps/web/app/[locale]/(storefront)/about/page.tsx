import { getTranslations } from "next-intl/server";
import { languageAlternates, canonicalUrl } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { StaticPageHeader, StaticPageBody } from "@/components/layout/StaticPage";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  const t = await getTranslations("Static.About");
  return {
    title: t("title"),
    alternates: { canonical: canonicalUrl("/about", locale), languages: languageAlternates("/about") },
  };
}

export default async function AboutPage() {
  const [tNav, t] = await Promise.all([getTranslations("Nav"), getTranslations("Static.About")]);
  const values = t.raw("values") as { title: string; body: string }[];

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: t("title") }]} />
      <StaticPageHeader title={t("title")} />
      <StaticPageBody>
        <div className="space-y-4">
          <p className="text-body leading-relaxed text-ink/70">{t("intro1")}</p>
          <p className="text-body leading-relaxed text-ink/70">{t("intro2")}</p>
        </div>

        <div>
          <h2 className="text-h3 font-semibold text-primary">{t("valuesHeading")}</h2>
          <div className="mt-4 grid gap-4 sm:grid-cols-3">
            {values.map((value) => (
              <div key={value.title} className="rounded-2xl bg-white p-6 shadow-soft">
                <p className="font-semibold text-primary">{value.title}</p>
                <p className="mt-2 text-small text-ink/60">{value.body}</p>
              </div>
            ))}
          </div>
        </div>
      </StaticPageBody>
    </>
  );
}

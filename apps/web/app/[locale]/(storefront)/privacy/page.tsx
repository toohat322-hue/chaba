import { getTranslations } from "next-intl/server";
import { languageAlternates, canonicalUrl } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { StaticPageHeader, StaticPageBody, StaticSection } from "@/components/layout/StaticPage";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  const t = await getTranslations("Static.Privacy");
  return {
    title: t("title"),
    alternates: { canonical: canonicalUrl("/privacy", locale), languages: languageAlternates("/privacy") },
  };
}

export default async function PrivacyPage() {
  const [tNav, t] = await Promise.all([getTranslations("Nav"), getTranslations("Static.Privacy")]);
  const sections = t.raw("sections") as { h: string; b: string }[];

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: t("title") }]} />
      <StaticPageHeader title={t("title")} intro={t("intro")} />
      <StaticPageBody>
        <p className="text-caption text-ink/40">{t("updated")}</p>
        {sections.map((section) => (
          <StaticSection key={section.h} heading={section.h} body={section.b} />
        ))}
      </StaticPageBody>
    </>
  );
}

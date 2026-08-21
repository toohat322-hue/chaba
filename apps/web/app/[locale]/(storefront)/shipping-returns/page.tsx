import { getTranslations } from "next-intl/server";
import { languageAlternates, canonicalUrl } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { StaticPageHeader, StaticPageBody, StaticSection } from "@/components/layout/StaticPage";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  const t = await getTranslations("Static.ShippingReturns");
  return {
    title: t("title"),
    alternates: {
      canonical: canonicalUrl("/shipping-returns", locale),
      languages: languageAlternates("/shipping-returns"),
    },
  };
}

export default async function ShippingReturnsPage() {
  const [tNav, t] = await Promise.all([getTranslations("Nav"), getTranslations("Static.ShippingReturns")]);
  const shippingSections = t.raw("shippingSections") as { h: string; b: string }[];
  const returnsSections = t.raw("returnsSections") as { h: string; b: string }[];

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: t("title") }]} />
      <StaticPageHeader title={t("title")} />
      <StaticPageBody>
        <div>
          <h2 className="text-h2 font-semibold text-primary">{t("shippingHeading")}</h2>
          <div className="mt-4 space-y-6">
            {shippingSections.map((section) => (
              <StaticSection key={section.h} heading={section.h} body={section.b} />
            ))}
          </div>
        </div>

        <div>
          <h2 className="text-h2 font-semibold text-primary">{t("returnsHeading")}</h2>
          <div className="mt-4 space-y-6">
            {returnsSections.map((section) => (
              <StaticSection key={section.h} heading={section.h} body={section.b} />
            ))}
          </div>
        </div>
      </StaticPageBody>
    </>
  );
}

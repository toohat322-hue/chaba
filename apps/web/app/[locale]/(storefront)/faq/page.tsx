import { getTranslations } from "next-intl/server";
import { languageAlternates, canonicalUrl, buildFaqJsonLd } from "@/lib/seo";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { StaticPageHeader, StaticPageBody } from "@/components/layout/StaticPage";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  const t = await getTranslations("Static.Faq");
  return {
    title: t("title"),
    alternates: { canonical: canonicalUrl("/faq", locale), languages: languageAlternates("/faq") },
  };
}

export default async function FaqPage() {
  const [tNav, t] = await Promise.all([getTranslations("Nav"), getTranslations("Static.Faq")]);
  const items = t.raw("items") as { q: string; a: string }[];
  const faqJsonLd = buildFaqJsonLd(items);

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd).replace(/</g, "\\u003c") }} />
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: t("title") }]} />
      <StaticPageHeader title={t("title")} />
      <StaticPageBody>
        <div className="divide-y divide-primary/10 overflow-hidden rounded-2xl bg-white shadow-soft">
          {items.map((item) => (
            <details key={item.q} className="group p-5 open:bg-background/40">
              <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-primary marker:content-none">
                {item.q}
                <span className="shrink-0 text-ink/40 transition-transform duration-200 group-open:rotate-45" aria-hidden="true">
                  +
                </span>
              </summary>
              <p className="mt-3 text-small leading-relaxed text-ink/70">{item.a}</p>
            </details>
          ))}
        </div>
      </StaticPageBody>
    </>
  );
}

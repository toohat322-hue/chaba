import { getTranslations } from "next-intl/server";
import { languageAlternates, canonicalUrl } from "@/lib/seo";
import { Link } from "@/i18n/navigation";
import { Breadcrumbs } from "@/components/layout/Breadcrumbs";
import { StaticPageHeader, StaticPageBody } from "@/components/layout/StaticPage";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props) {
  const { locale } = await params;
  const t = await getTranslations("Static.Contact");
  return {
    title: t("title"),
    alternates: { canonical: canonicalUrl("/contact", locale), languages: languageAlternates("/contact") },
  };
}

const SUPPORT_EMAIL = "support@chaba.dz";

export default async function ContactPage() {
  const [tNav, tContact] = await Promise.all([getTranslations("Nav"), getTranslations("Static.Contact")]);

  const rawNumber = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER;
  const number = rawNumber?.replace(/[^0-9]/g, "");
  const whatsappHref = number
    ? `https://wa.me/${number}?text=${encodeURIComponent(tNav("whatsappMessage"))}`
    : undefined;

  return (
    <>
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: tContact("title") }]} />
      <StaticPageHeader title={tContact("title")} intro={tContact("intro")} />
      <StaticPageBody>
        <div className="grid gap-4 sm:grid-cols-3">
          {whatsappHref && (
            <a
              href={whatsappHref}
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-2xl bg-white p-6 shadow-soft transition-shadow hover:shadow-lift"
            >
              <p className="font-semibold text-primary">{tContact("whatsappHeading")}</p>
              <p className="mt-2 text-small text-ink/60">{tContact("whatsappBody")}</p>
              <p className="mt-4 text-small font-medium text-accent">{tContact("whatsappCta")} ←</p>
            </a>
          )}

          <a href={`mailto:${SUPPORT_EMAIL}`} className="rounded-2xl bg-white p-6 shadow-soft transition-shadow hover:shadow-lift">
            <p className="font-semibold text-primary">{tContact("emailHeading")}</p>
            <p className="mt-2 text-small text-ink/60">{tContact("emailBody")}</p>
            <p className="mt-4 text-small font-medium text-accent" dir="ltr">
              {SUPPORT_EMAIL}
            </p>
          </a>

          <Link href="/track-order" className="rounded-2xl bg-white p-6 shadow-soft transition-shadow hover:shadow-lift">
            <p className="font-semibold text-primary">{tContact("trackOrderHeading")}</p>
            <p className="mt-2 text-small text-ink/60">{tContact("trackOrderBody")}</p>
            <p className="mt-4 text-small font-medium text-accent">{tContact("trackOrderCta")} ←</p>
          </Link>
        </div>
      </StaticPageBody>
    </>
  );
}

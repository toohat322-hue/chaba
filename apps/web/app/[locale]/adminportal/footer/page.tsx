"use client";

import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";

const CARDS = [
  { key: "footerAboutTitle", href: "/adminportal/footer/about" },
  { key: "footerFeaturesTitle", href: "/adminportal/footer/features" },
  { key: "footerColumnsTitle", href: "/adminportal/footer/columns" },
  { key: "footerSocialLinksTitle", href: "/adminportal/footer/social-links" },
  { key: "footerPaymentMethodsTitle", href: "/adminportal/footer/payment-methods" },
  { key: "footerSubscribersTitle", href: "/adminportal/footer/subscribers" },
] as const;

export default function AdminFooterHubPage() {
  const t = useTranslations("Admin");

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("footerSettingsTitle")}</h1>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {CARDS.map((card) => (
          <Link
            key={card.key}
            href={card.href}
            className="rounded-2xl bg-white p-6 shadow-soft transition-shadow hover:shadow-lift"
          >
            <p className="font-semibold text-primary">{t(card.key)}</p>
          </Link>
        ))}
      </div>
    </div>
  );
}

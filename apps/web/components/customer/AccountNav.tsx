"use client";

import { useTranslations } from "next-intl";
import { Link, usePathname } from "@/i18n/navigation";

export function AccountNav() {
  const t = useTranslations("Auth");
  const pathname = usePathname();

  const items = [
    { href: "/orders", label: t("myOrders") },
    { href: "/account", label: t("accountSettings") },
    { href: "/account/addresses", label: t("myAddresses") },
    { href: "/account/security", label: t("securityTitle") },
  ];

  return (
    <nav className="mb-6 flex gap-5 border-b border-primary/10 pb-3 text-small">
      {items.map((item) => {
        const active = pathname === item.href;
        return (
          <Link
            key={item.href}
            href={item.href}
            className={active ? "font-semibold text-primary" : "text-ink/60 hover:text-ink"}
          >
            {item.label}
          </Link>
        );
      })}
    </nav>
  );
}

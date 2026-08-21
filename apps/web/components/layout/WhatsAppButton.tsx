"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { usePathname } from "@/i18n/navigation";
import type { FooterData } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

function WhatsAppIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className="h-7 w-7">
      <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.32 4.96L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.09c-.24.68-1.4 1.32-1.93 1.4-.5.08-1.11.11-1.79-.11a15 15 0 0 1-2.05-.75c-3.6-1.55-5.94-5.16-6.12-5.4-.18-.24-1.46-1.94-1.46-3.7s.93-2.62 1.26-2.98c.32-.35.71-.44.94-.44l.68.01c.22 0 .5-.08.79.6.29.71.99 2.45 1.08 2.62.09.18.15.39.03.63-.12.24-.18.39-.36.6-.18.21-.38.47-.54.63-.18.18-.37.37-.16.73.21.35.93 1.53 2 2.48 1.37 1.22 2.53 1.6 2.89 1.78.35.18.56.15.77-.09.21-.24.9-1.05 1.14-1.41.24-.35.47-.29.79-.18.32.12 2.05.97 2.4 1.14.35.18.59.26.68.41.09.15.09.85-.15 1.53z" />
    </svg>
  );
}

export function WhatsAppButton({ whatsapp }: { whatsapp: FooterData["settings"]["whatsapp"] | undefined }) {
  const t = useTranslations("Nav");
  const locale = useLocale() as Locale;
  const pathname = usePathname();
  // Number/active state now come from Admin Settings (StoreSetting) via the
  // shared /footer fetch in the storefront layout — no more env var, so a
  // real number can be set (and toggled off) without a redeploy.
  const number = whatsapp?.number?.replace(/[^0-9]/g, "");

  // Only the home page stacks a hero + trust bar tall enough that, on short
  // mobile viewports with longer translated copy (e.g. wrapped English
  // headlines), the trust bar's bottom edge can land inside the button's
  // fixed bottom-corner zone. Rather than chase a per-locale/per-viewport
  // magic number, the button simply stays out of the way until the user
  // has scrolled past that block — everywhere else it's shown immediately.
  const isHomePage = pathname === "/";
  const [visible, setVisible] = useState(!isHomePage);

  useEffect(() => {
    // Non-home pages start (and stay) visible via the initial state above —
    // this effect only has scroll-tracking work to do on the home page.
    if (!isHomePage) return;

    if (window.scrollY > 120) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- handles the "landed already scrolled" case (e.g. back-navigation); not derived from render
      setVisible(true);
    }

    function onScroll() {
      if (window.scrollY > 120) setVisible(true);
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [isHomePage]);

  if (!number || !visible) return null;

  const message = whatsapp?.message[locale] || t("whatsappMessage");
  const href = `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
  // Only the product page has a fixed mobile sticky bar (VariantSelector) at
  // the very bottom of the screen — everywhere else the button can sit
  // closer to the edge instead of reserving that clearance unnecessarily.
  const onProductPage = pathname.startsWith("/products/");

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      aria-label={t("whatsapp")}
      className={`group animate-fab-in fixed start-4 z-40 flex h-14 items-center rounded-full bg-gradient-to-br from-[#2CE072] to-[#20B858] text-white shadow-lift transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_18px_40px_rgba(32,184,88,0.4)] sm:bottom-6 sm:start-6 ${
        onProductPage ? "bottom-20" : "bottom-6"
      }`}
    >
      <span className="relative flex h-14 w-14 shrink-0 items-center justify-center">
        <span
          className="animate-fab-ring pointer-events-none absolute inset-0 rounded-full border-2 border-white/70"
          aria-hidden="true"
        />
        <WhatsAppIcon />
      </span>
      <span className="max-w-0 overflow-hidden whitespace-nowrap text-small font-semibold opacity-0 transition-all duration-300 ease-out group-hover:max-w-40 group-hover:opacity-100 group-hover:pe-5">
        {t("whatsapp")}
      </span>
    </a>
  );
}

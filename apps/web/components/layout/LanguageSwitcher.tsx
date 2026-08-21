"use client";

import { useEffect, useRef, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { usePathname, useRouter } from "@/i18n/navigation";
import { routing } from "@/i18n/routing";

type Locale = (typeof routing.locales)[number];

const NATIVE_NAME: Record<Locale, string> = {
  ar: "العربية",
  fr: "Français",
  en: "English",
};

function GlobeIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="h-[18px] w-[18px]">
      <circle cx="12" cy="12" r="9" />
      <path d="M3 12h18M12 3c2.5 2.5 4 5.8 4 9s-1.5 6.5-4 9c-2.5-2.5-4-5.8-4-9s1.5-6.5 4-9z" strokeLinecap="round" />
    </svg>
  );
}

function CheckIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.25" className="h-4 w-4">
      <path d="M5 12.5l4.5 4.5L19 7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export function LanguageSwitcher() {
  const locale = useLocale() as Locale;
  const t = useTranslations("Nav");
  const pathname = usePathname();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;

    function handleClick(event: MouseEvent) {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    function handleKey(event: KeyboardEvent) {
      if (event.key === "Escape") setOpen(false);
    }

    document.addEventListener("mousedown", handleClick);
    document.addEventListener("keydown", handleKey);
    return () => {
      document.removeEventListener("mousedown", handleClick);
      document.removeEventListener("keydown", handleKey);
    };
  }, [open]);

  function handleSelect(nextLocale: Locale) {
    setOpen(false);
    if (nextLocale === locale) return;
    router.replace(pathname, { locale: nextLocale });
  }

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        aria-label={t("language")}
        aria-expanded={open}
        className="flex items-center gap-1 rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
      >
        <GlobeIcon />
        <span className="text-caption font-semibold uppercase">{locale}</span>
      </button>

      {open && (
        <div className="absolute end-0 top-full z-50 mt-2 w-36 overflow-hidden rounded-2xl border border-primary/10 bg-white py-1 shadow-lift">
          {routing.locales.map((loc) => (
            <button
              key={loc}
              type="button"
              onClick={() => handleSelect(loc)}
              aria-current={loc === locale}
              className={`flex w-full items-center justify-between gap-2 px-4 py-2.5 text-start text-small transition-colors hover:bg-primary/5 ${
                loc === locale ? "font-semibold text-primary" : "text-ink/75"
              }`}
            >
              {NATIVE_NAME[loc]}
              {loc === locale && <CheckIcon />}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

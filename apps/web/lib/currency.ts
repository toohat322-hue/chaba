import type { routing } from "@/i18n/routing";

type Locale = (typeof routing.locales)[number];

const BCP47: Record<Locale, string> = {
  ar: "ar-DZ",
  fr: "fr-DZ",
  en: "en-US",
};

export const CURRENCY_LABEL: Record<Locale, string> = {
  ar: "د.ج",
  fr: "DA",
  en: "DZD",
};

/**
 * Formats an integer DZD-centimes amount for display. Algerian convention
 * (PRD A6): no decimals shown, though the value is stored/passed as centimes
 * to avoid floating-point rounding anywhere in the pipeline.
 */
export function formatPrice(centimes: number, locale: Locale): string {
  const dzd = Math.round(centimes / 100);
  const formatted = new Intl.NumberFormat(BCP47[locale], {
    maximumFractionDigits: 0,
  }).format(dzd);

  return `${formatted} ${CURRENCY_LABEL[locale]}`;
}

const UNIT_LABEL: Record<Locale, Record<string, string>> = {
  ar: { ml: "مل", g: "غ", oz: "أونصة" },
  fr: { ml: "ml", g: "g", oz: "oz" },
  en: { ml: "ml", g: "g", oz: "oz" },
};

/**
 * "10 مل" / "10 ml" / "10 ml" — a size number is currency-agnostic (PRD:
 * never tie a variant's size to the storefront's currency/locale switch),
 * only the unit word itself is translated, same self-contained per-locale
 * label pattern as formatPrice above.
 */
export function formatSize(value: number, unit: string, locale: Locale): string {
  const label = UNIT_LABEL[locale][unit] ?? unit;
  const formattedValue = new Intl.NumberFormat(BCP47[locale], { maximumFractionDigits: 2 }).format(value);

  return `${formattedValue} ${label}`;
}

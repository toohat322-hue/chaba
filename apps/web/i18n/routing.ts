import { defineRouting } from "next-intl/routing";

// PRD §9/§14: Arabic is the default/primary layout direction, not a mirrored
// afterthought of an LTR design — ar is listed first and is the default locale.
// localeDetection is off: a visitor's browser Accept-Language header must not
// override that default (e.g. an Algerian visitor with an English-language OS
// should still land on the Arabic storefront, not be auto-routed to /en).
// Language stays a deliberate switch the visitor makes, not a guess.
export const routing = defineRouting({
  locales: ["ar", "fr", "en"],
  defaultLocale: "ar",
  localeDetection: false,
});

export const rtlLocales: ReadonlySet<string> = new Set(["ar"]);

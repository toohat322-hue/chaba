import type { Metadata } from "next";
import { Cairo, Inter } from "next/font/google";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { getTranslations } from "next-intl/server";
import { notFound } from "next/navigation";
import { routing, rtlLocales } from "@/i18n/routing";
import { SITE_URL, buildOpenGraph } from "@/lib/seo";
import "../globals.css";

// Empty/unset until a real value is provided (see .env.local.example) — no
// placeholder ID is ever shipped, so this renders nothing until the user
// supplies theirs.
const GSC_VERIFICATION = process.env.NEXT_PUBLIC_GSC_VERIFICATION;

// PRD §9: Cairo (Arabic, primary) + Inter (French/English, complementary geometric sans).
const cairo = Cairo({
  variable: "--font-arabic",
  subsets: ["arabic"],
});

const inter = Inter({
  variable: "--font-latin",
  subsets: ["latin"],
});

export async function generateMetadata({
  params,
}: LayoutProps<"/[locale]">): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "Meta" });

  return {
    metadataBase: new URL(SITE_URL),
    title: { default: t("title"), template: `%s — CHABA` },
    description: t("description"),
    openGraph: buildOpenGraph({ locale, title: t("title"), description: t("description") }),
    icons: { icon: "/brand/logo.png", apple: "/brand/logo.png" },
    ...(GSC_VERIFICATION && { verification: { google: GSC_VERIFICATION } }),
  };
}

export function generateViewport() {
  return { themeColor: "#0e3b36" };
}

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export default async function LocaleLayout({
  children,
  params,
}: LayoutProps<"/[locale]">) {
  const { locale } = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }

  const dir = rtlLocales.has(locale) ? "rtl" : "ltr";

  return (
    <html
      lang={locale}
      dir={dir}
      className={`${cairo.variable} ${inter.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col bg-background text-ink">
        <NextIntlClientProvider>{children}</NextIntlClientProvider>
      </body>
    </html>
  );
}

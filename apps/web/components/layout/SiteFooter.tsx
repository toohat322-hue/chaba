"use client";

import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import type { FooterData } from "@/lib/api";
import { FEATURE_ICONS, SOCIAL_ICONS, PAYMENT_ICONS, GenericIcon } from "./FooterIcons";
import { NewsletterForm } from "./NewsletterForm";

type Locale = "ar" | "fr" | "en";

function FooterColumnLinks({
  column,
  locale,
  className,
}: {
  column: FooterData["columns"][number];
  locale: Locale;
  className?: string;
}) {
  return (
    <ul className={`space-y-2.5 ${className ?? ""}`}>
      {column.links.map((link, i) => (
        <li key={`${link.url}-${i}`}>
          <Link href={link.url} className="text-small text-background/70 transition-colors hover:text-accent">
            {link.label[locale]}
          </Link>
        </li>
      ))}
    </ul>
  );
}

export function SiteFooter({ footer }: { footer: FooterData | null }) {
  const tHome = useTranslations("Home");
  const tFooter = useTranslations("Footer");
  const locale = useLocale() as Locale;

  // Backend unreachable: a minimal, honest fallback rather than a broken page.
  if (!footer) {
    return (
      <footer className="bg-primary">
        <div className="mx-auto max-w-7xl px-4 py-8 text-center text-caption text-background/60">
          © {new Date().getFullYear()} {tHome("title")}
        </div>
      </footer>
    );
  }

  const { settings, features, socialLinks, columns, paymentMethods } = footer;

  return (
    <footer className="bg-primary text-background">
      <div className="flex flex-col items-center gap-3 pt-10 text-center">
        <Image
          src="/brand/logo.png"
          alt={tHome("title")}
          width={56}
          height={56}
          className="h-14 w-14 rounded-xl object-cover ring-1 ring-accent/30"
        />
        <p className="text-h3 font-semibold tracking-wide text-background">{tHome("title")}</p>
        <p className="text-small text-background/60">{tFooter("brandTagline")}</p>

        {socialLinks.length > 0 && (
          <div className="mt-2 flex items-center gap-3">
            {socialLinks.map((social, i) => {
              const Icon = SOCIAL_ICONS[social.platform] ?? GenericIcon;
              return (
                <a
                  key={`${social.platform}-${i}`}
                  href={social.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={social.platform}
                  className="flex h-9 w-9 items-center justify-center rounded-full border border-accent/40 bg-black/15 text-accent transition-colors hover:bg-accent hover:text-primary"
                >
                  <Icon className="h-4 w-4" />
                </a>
              );
            })}
          </div>
        )}
      </div>

      {features.length > 0 && (
        <div className="mt-8 border-b border-background/10">
          <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-10 gap-y-4 px-6 py-6">
            {features.map((feature, i) => {
              const Icon = FEATURE_ICONS[feature.icon] ?? GenericIcon;
              return (
                <div key={feature.id} className="flex items-center gap-4">
                  {i > 0 && <span className="hidden h-8 w-px bg-background/15 sm:block" aria-hidden="true" />}
                  <div className="flex items-center gap-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                      <Icon className="h-5 w-5" />
                    </span>
                    <div>
                      <p className="text-small font-semibold text-background">{feature.title[locale]}</p>
                      {feature.description[locale] && (
                        <p className="text-caption text-background/60">{feature.description[locale]}</p>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid gap-x-8 gap-y-2 md:grid-cols-2 md:gap-y-10 lg:grid-cols-4 lg:gap-10">
          {settings.about.title[locale] && (
            <div className="pb-4 md:pb-0">
              <p className="relative inline-block pb-2 text-small font-semibold text-background after:absolute after:bottom-0 after:start-0 after:h-0.5 after:w-6 after:rounded-full after:bg-accent after:content-['']">
                {settings.about.title[locale]}
              </p>
              {settings.about.description[locale] && (
                <p className="mt-4 text-small leading-relaxed text-background/70">{settings.about.description[locale]}</p>
              )}
            </div>
          )}

          {columns.map((column) => (
            <div key={column.id}>
              {/* Mobile+tablet: a real collapsible accordion (native <details>,
                  no JS). Desktop: plain always-visible column. These are two
                  separate elements — not one <details> forced open past a
                  breakpoint via CSS — because a closed <details>'s children
                  are hidden through the browser's own content-visibility
                  handling in some engines, not just a plain overridable
                  `display: none`, so a CSS override to force it open doesn't
                  reliably work everywhere. */}
              <details className="group border-b border-background/10 py-4 md:hidden">
                <summary className="flex cursor-pointer list-none items-center justify-between text-small font-semibold text-background marker:content-none">
                  {column.title[locale]}
                  <span className="text-background/50 transition-transform duration-200 group-open:rotate-45" aria-hidden="true">
                    +
                  </span>
                </summary>
                <FooterColumnLinks column={column} locale={locale} className="mt-3" />
              </details>

              <div className="hidden md:block">
                <p className="relative inline-block pb-2 text-small font-semibold text-background after:absolute after:bottom-0 after:start-0 after:h-0.5 after:w-6 after:rounded-full after:bg-accent after:content-['']">
                  {column.title[locale]}
                </p>
                <FooterColumnLinks column={column} locale={locale} className="mt-4" />
              </div>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-2xl border border-background/10 bg-background/5 p-6 sm:p-8">
          <NewsletterForm />
        </div>
      </div>

      <div className="border-t border-background/10">
        <div className="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-6 sm:flex-row sm:justify-between sm:px-6 lg:px-8">
          <p className="text-caption text-background/50">
            © {new Date().getFullYear()} {tHome("title")}. {tFooter("rightsReserved")}
          </p>
          {paymentMethods.length > 0 && (
            <div className="flex flex-wrap items-center justify-center gap-3">
              <span className="text-caption text-background/50">{tFooter("securePaymentLabel")}</span>
              <div className="flex flex-wrap items-center gap-2">
                {paymentMethods.map((method, i) => {
                  const Badge = PAYMENT_ICONS[method.icon] ?? GenericIcon;
                  return (
                    <span key={`${method.icon}-${i}`} title={method.name[locale]}>
                      <Badge />
                    </span>
                  );
                })}
              </div>
            </div>
          )}
        </div>
      </div>
    </footer>
  );
}

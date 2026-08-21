"use client";

import { useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { subscribeNewsletter } from "@/lib/api";

function MailIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="h-5 w-5" aria-hidden="true">
      <rect x="2.5" y="5" width="19" height="14" rx="2.5" />
      <path d="M3.5 6.5l8.5 6.5 8.5-6.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export function NewsletterForm() {
  const t = useTranslations("Footer");
  const locale = useLocale();

  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<"idle" | "loading" | "success" | "error">("idle");

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setStatus("loading");

    try {
      await subscribeNewsletter(email, locale);
      setStatus("success");
      setEmail("");
    } catch {
      // Reason (invalid vs. duplicate) isn't distinguished in copy — the
      // backend's validation message isn't localized, so surfacing it
      // directly would break AR/FR readers; see Footer.subscribeError.
      setStatus("error");
    }
  }

  return (
    <div>
      <form onSubmit={handleSubmit} className="flex flex-col gap-6 sm:flex-row sm:items-center sm:gap-0">
        <div className="flex items-center gap-3 sm:flex-1 sm:border-e sm:border-background/15 sm:pe-6">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-background/10 text-accent">
            <MailIcon />
          </span>
          <div>
            <p className="text-small font-semibold text-background">{t("newsletterHeading")}</p>
            <p className="text-caption text-background/60">{t("newsletterBody")}</p>
          </div>
        </div>

        <div className="flex items-center gap-2 sm:flex-1 sm:justify-center sm:border-e sm:border-background/15 sm:px-6">
          <span className="text-background/50">
            <MailIcon />
          </span>
          <input
            type="email"
            required
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            placeholder={t("emailPlaceholder")}
            dir="ltr"
            className="min-w-0 flex-1 bg-transparent text-small text-background placeholder:text-background/50 focus:outline-none"
          />
        </div>

        <div className="sm:ps-6">
          <button
            type="submit"
            disabled={status === "loading"}
            className="w-full shrink-0 rounded-full bg-accent px-8 py-2.5 text-small font-semibold text-primary transition-colors hover:bg-accent/90 disabled:opacity-60 sm:w-auto"
          >
            {status === "loading" ? t("subscribeLoading") : t("subscribeButton")}
          </button>
        </div>
      </form>

      <p role="status" aria-live="polite" className="mt-3 min-h-[1.25rem] text-caption">
        {status === "success" && <span className="text-accent">{t("subscribeSuccess")}</span>}
        {status === "error" && <span className="text-[#f28b82]">{t("subscribeError")}</span>}
      </p>
    </div>
  );
}

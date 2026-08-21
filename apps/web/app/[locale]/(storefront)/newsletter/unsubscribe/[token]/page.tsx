"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { ApiError, unsubscribeNewsletter } from "@/lib/api";
import { ShabaLoader } from "@/components/ui/ShabaLoader";

export default function NewsletterUnsubscribePage() {
  const t = useTranslations("NewsletterUnsubscribe");
  const tLoading = useTranslations("Loading");
  const params = useParams<{ token: string }>();

  const [status, setStatus] = useState<"pending" | "success" | "error">("pending");

  useEffect(() => {
    unsubscribeNewsletter(params.token)
      .then(() => setStatus("success"))
      .catch((err) => {
        // An already-used or invalid token still reads as "you're not
        // subscribed" to the visitor — not a scary error, since the
        // practical outcome (no longer receiving the newsletter) is the
        // same either way.
        setStatus(err instanceof ApiError ? "success" : "error");
      });
  }, [params.token]);

  return (
    <div className="mx-auto flex max-w-lg flex-col items-center gap-4 px-6 py-24 text-center">
      {status === "pending" && <ShabaLoader label={tLoading("label")} />}

      {status === "success" && (
        <>
          <h1 className="text-h2 font-semibold text-primary">{t("successTitle")}</h1>
          <p className="text-body text-ink/70">{t("successBody")}</p>
        </>
      )}

      {status === "error" && (
        <>
          <h1 className="text-h2 font-semibold text-primary">{t("errorTitle")}</h1>
          <p className="text-body text-ink/70">{t("errorBody")}</p>
        </>
      )}

      {status !== "pending" && (
        <Link
          href="/"
          className="mt-2 rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90"
        >
          {t("backToHome")}
        </Link>
      )}
    </div>
  );
}

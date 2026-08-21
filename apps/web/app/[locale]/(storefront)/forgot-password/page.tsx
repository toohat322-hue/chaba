"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { ApiError, sendPasswordResetOtp } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function ForgotPasswordPage() {
  const t = useTranslations("Auth");
  const router = useRouter();

  const [phone, setPhone] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await sendPasswordResetOtp(phone);
      router.push(`/reset-password?phone=${encodeURIComponent(phone)}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("forgotPasswordError"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-6 py-16">
      <h1 className="mb-2 text-h1 font-semibold text-primary">{t("forgotPasswordTitle")}</h1>
      <p className="mb-6 text-small text-ink/60">{t("forgotPasswordInstructions")}</p>

      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="tel"
          required
          autoComplete="tel"
          placeholder={t("phone")}
          value={phone}
          onChange={(event) => setPhone(event.target.value)}
          className={inputClass}
        />

        {error && (
          <p className="text-small text-error" role="alert">
            {error}
          </p>
        )}

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {submitting ? t("loading") : t("sendCode")}
        </button>
      </form>

      <p className="mt-6 text-center text-small text-ink/60">
        <Link href="/login" className="font-medium text-primary hover:underline">
          {t("backToLogin")}
        </Link>
      </p>
    </div>
  );
}

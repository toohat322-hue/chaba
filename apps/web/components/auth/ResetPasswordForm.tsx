"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { ApiError, resetPassword, sendPasswordResetOtp } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export function ResetPasswordForm({ initialPhone }: { initialPhone: string }) {
  const t = useTranslations("Auth");

  const [phone, setPhone] = useState(initialPhone);
  const [code, setCode] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [resending, setResending] = useState(false);
  const [resendMessage, setResendMessage] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await resetPassword({
        phone,
        code,
        new_password: newPassword,
        new_password_confirmation: confirmation,
      });
      setSuccess(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("resetPasswordError"));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleResend() {
    if (!phone) return;

    setResendMessage(null);
    setError(null);
    setResending(true);
    try {
      await sendPasswordResetOtp(phone);
      setResendMessage(t("codeSentMessage"));
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("resetPasswordError"));
    } finally {
      setResending(false);
    }
  }

  if (success) {
    return (
      <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col items-center justify-center px-6 py-16 text-center">
        <h1 className="mb-2 text-h1 font-semibold text-primary">{t("resetPasswordSuccessTitle")}</h1>
        <p className="mb-6 text-body text-ink/60">{t("resetPasswordSuccessMessage")}</p>
        <Link
          href="/login"
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90"
        >
          {t("goToLogin")}
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-6 py-16">
      <h1 className="mb-2 text-h1 font-semibold text-primary">{t("resetPasswordTitle")}</h1>
      <p className="mb-6 text-small text-ink/60">{t("resetPasswordInstructions")}</p>

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
        <input
          type="text"
          required
          inputMode="numeric"
          autoComplete="one-time-code"
          maxLength={6}
          placeholder={t("code")}
          value={code}
          onChange={(event) => setCode(event.target.value)}
          className={inputClass}
        />
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={t("newPassword")}
          value={newPassword}
          onChange={(event) => setNewPassword(event.target.value)}
          className={inputClass}
        />
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={t("confirmPassword")}
          value={confirmation}
          onChange={(event) => setConfirmation(event.target.value)}
          className={inputClass}
        />

        {error && (
          <p className="text-small text-error" role="alert">
            {error}
          </p>
        )}
        {resendMessage && <p className="text-small text-success">{resendMessage}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {submitting ? t("loading") : t("resetPasswordSubmit")}
        </button>
      </form>

      <div className="mt-6 flex items-center justify-between text-small text-ink/60">
        <button
          type="button"
          onClick={handleResend}
          disabled={resending || !phone}
          className="font-medium text-primary hover:underline disabled:opacity-50"
        >
          {t("resendCode")}
        </button>
        <Link href="/login" className="font-medium text-primary hover:underline">
          {t("backToLogin")}
        </Link>
      </div>
    </div>
  );
}

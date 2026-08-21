"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";
import { useAdminAuth } from "@/components/admin/AdminAuthProvider";

export default function AdminLoginPage() {
  const t = useTranslations("Admin");
  const router = useRouter();
  const { login, verifyTwoFactor } = useAdminAuth();

  const [loginValue, setLoginValue] = useState("");
  const [password, setPassword] = useState("");
  const [code, setCode] = useState("");
  const [twoFactorToken, setTwoFactorToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      const result = await login(loginValue, password);
      if (result.requiresTwoFactor) {
        setTwoFactorToken(result.twoFactorToken);
      } else {
        router.push("/admin");
      }
    } catch {
      setError(t("loginError"));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleVerify(event: FormEvent) {
    event.preventDefault();
    if (!twoFactorToken) return;
    setError(null);
    setSubmitting(true);

    try {
      await verifyTwoFactor(twoFactorToken, code);
      router.push("/admin");
    } catch {
      setError(t("twoFactorCodeError"));
    } finally {
      setSubmitting(false);
    }
  }

  if (twoFactorToken) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-primary px-6">
        <form onSubmit={handleVerify} className="w-full max-w-sm rounded-2xl bg-background p-8 shadow-soft">
          <h1 className="mb-2 text-h2 font-semibold text-primary">{t("twoFactorTitle")}</h1>
          <p className="mb-6 text-small text-ink/60">{t("twoFactorPrompt")}</p>

          <label className="mb-6 block">
            <span className="mb-1 block text-small font-medium text-ink/70">{t("twoFactorCodeField")}</span>
            <input
              type="text"
              inputMode="numeric"
              autoComplete="one-time-code"
              autoFocus
              required
              value={code}
              onChange={(event) => setCode(event.target.value)}
              className="w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-center text-h3 tracking-[0.3em] text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
              maxLength={20}
            />
          </label>

          {error && <p className="mb-4 text-small text-error">{error}</p>}

          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {t("twoFactorSubmit")}
          </button>
        </form>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-primary px-6">
      <form onSubmit={handleSubmit} className="w-full max-w-sm rounded-2xl bg-background p-8 shadow-soft">
        <h1 className="mb-6 text-h2 font-semibold text-primary">{t("loginTitle")}</h1>

        <label className="mb-4 block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("loginField")}</span>
          <input
            type="text"
            required
            autoComplete="username"
            value={loginValue}
            onChange={(event) => setLoginValue(event.target.value)}
            className="w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>

        <label className="mb-6 block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("passwordField")}</span>
          <input
            type="password"
            required
            autoComplete="current-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            className="w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>

        {error && <p className="mb-4 text-small text-error">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("loginSubmit")}
        </button>
      </form>
    </div>
  );
}

"use client";

import { Suspense, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { Link, useRouter } from "@/i18n/navigation";
import { useCustomerAuth, TwoFactorRequiredError } from "@/components/customer/CustomerAuthProvider";
import { SocialLoginButtons } from "@/components/customer/SocialLoginButtons";
import { sanitizeReturnTo } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function LoginForm() {
  const t = useTranslations("Auth");
  const router = useRouter();
  const locale = useLocale();
  const searchParams = useSearchParams();
  const { login } = useCustomerAuth();

  const returnTo = sanitizeReturnTo(searchParams.get("return_to"));

  const [loginValue, setLoginValue] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await login(loginValue, password);
      router.push(returnTo);
    } catch (err) {
      // Two-factor is fully supported for social login (see the auth
      // callback page) but not yet for the password form here — an honest
      // message beats a wrong "incorrect credentials" one.
      setError(err instanceof TwoFactorRequiredError ? t("twoFactorNotSupportedHere") : t("loginError"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-6 py-16">
      <h1 className="mb-1 text-h1 font-semibold text-primary">{t("welcomeHeading")}</h1>
      <p className="mb-6 text-body text-ink/60">{t("welcomeSubheading")}</p>

      <SocialLoginButtons returnTo={returnTo} locale={locale} />

      <div className="my-6 flex items-center gap-3">
        <div className="h-px flex-1 bg-primary/10" />
        <span className="text-caption font-medium text-ink/40">{t("orDivider")}</span>
        <div className="h-px flex-1 bg-primary/10" />
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("phoneOrEmail")}</span>
          <input
            type="text"
            required
            autoComplete="username"
            value={loginValue}
            onChange={(event) => setLoginValue(event.target.value)}
            className={inputClass}
          />
        </label>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("password")}</span>
          <input
            type="password"
            required
            autoComplete="current-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            className={inputClass}
          />
        </label>

        <Link href="/forgot-password" className="block text-small font-medium text-primary hover:underline">
          {t("forgotPasswordLink")}
        </Link>

        {error && <p className="text-small text-error">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("loginSubmit")}
        </button>
      </form>

      <p className="mt-6 text-center text-small text-ink/60">
        {t("noAccountYet")}{" "}
        <Link href="/register" className="font-medium text-primary hover:underline">
          {t("goToRegister")}
        </Link>
      </p>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginForm />
    </Suspense>
  );
}

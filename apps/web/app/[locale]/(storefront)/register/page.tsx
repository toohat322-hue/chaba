"use client";

import { Suspense, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { Link, useRouter } from "@/i18n/navigation";
import { ApiError, sanitizeReturnTo } from "@/lib/api";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { SocialLoginButtons } from "@/components/customer/SocialLoginButtons";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function RegisterForm() {
  const t = useTranslations("Auth");
  const router = useRouter();
  const locale = useLocale();
  const searchParams = useSearchParams();
  const { register } = useCustomerAuth();

  const returnTo = sanitizeReturnTo(searchParams.get("return_to"));

  const [fullName, setFullName] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await register({
        full_name: fullName,
        phone,
        email: email || undefined,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.push(returnTo);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("registerError"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-6 py-16">
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("registerTitle")}</h1>

      <SocialLoginButtons returnTo={returnTo} locale={locale} />

      <div className="my-6 flex items-center gap-3">
        <div className="h-px flex-1 bg-primary/10" />
        <span className="text-caption font-medium text-ink/40">{t("orDivider")}</span>
        <div className="h-px flex-1 bg-primary/10" />
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="text"
          required
          placeholder={t("fullName")}
          value={fullName}
          onChange={(event) => setFullName(event.target.value)}
          className={inputClass}
        />
        <input
          type="tel"
          required
          placeholder={t("phone")}
          value={phone}
          onChange={(event) => setPhone(event.target.value)}
          className={inputClass}
        />
        <input
          type="email"
          placeholder={t("emailOptional")}
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          className={inputClass}
        />
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={t("password")}
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          className={inputClass}
        />
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={t("confirmPassword")}
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          className={inputClass}
        />

        {error && <p className="text-small text-error">{error}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("registerSubmit")}
        </button>
      </form>

      <p className="mt-6 text-center text-small text-ink/60">
        {t("haveAccountAlready")}{" "}
        <Link href="/login" className="font-medium text-primary hover:underline">
          {t("goToLogin")}
        </Link>
      </p>
    </div>
  );
}

export default function RegisterPage() {
  return (
    <Suspense fallback={null}>
      <RegisterForm />
    </Suspense>
  );
}

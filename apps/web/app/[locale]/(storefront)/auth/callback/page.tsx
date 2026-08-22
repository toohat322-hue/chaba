"use client";

import { Suspense, useEffect, useRef, useState, type FormEvent } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import { ApiError, customerVerifyTwoFactor, exchangeSocialCode, sanitizeReturnTo } from "@/lib/api";

type Status = "exchanging" | "two_factor" | "error" | "done";

function AuthCallbackContent() {
  const t = useTranslations("Auth");
  const router = useRouter();
  const searchParams = useSearchParams();
  const { setSession } = useCustomerAuth();

  const returnTo = sanitizeReturnTo(searchParams.get("return_to"));
  const providerError = searchParams.get("error");
  const code = searchParams.get("code");

  const [status, setStatus] = useState<Status>(providerError ? "error" : "exchanging");
  const [twoFactorToken, setTwoFactorToken] = useState<string | null>(null);
  const [otpCode, setOtpCode] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const started = useRef(false);

  useEffect(() => {
    // React Strict Mode invokes effects twice in dev — the exchange code is
    // single-use, so a second call would surface a spurious "expired" error
    // for what's actually a perfectly normal login.
    if (started.current || providerError || !code) return;
    started.current = true;

    exchangeSocialCode(code)
      .then(async (data) => {
        if (data.requires_two_factor) {
          setTwoFactorToken(data.two_factor_token);
          setStatus("two_factor");
          return;
        }

        await setSession(data.user);
        setStatus("done");
        router.push(returnTo);
      })
      .catch((err) => {
        setError(err instanceof ApiError ? err.message : t("socialLoginError"));
        setStatus("error");
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- runs once for this one-time exchange code, not on every render
  }, []);

  async function handleVerifyTwoFactor(event: FormEvent) {
    event.preventDefault();
    if (!twoFactorToken) return;
    setError(null);
    setSubmitting(true);

    try {
      const data = await customerVerifyTwoFactor(twoFactorToken, otpCode);
      await setSession(data.user);
      setStatus("done");
      router.push(returnTo);
    } catch {
      setError(t("twoFactorCodeError"));
    } finally {
      setSubmitting(false);
    }
  }

  if (status === "two_factor") {
    return (
      <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-6 py-16">
        <h1 className="mb-2 text-h1 font-semibold text-primary">{t("twoFactorTitle")}</h1>
        <p className="mb-6 text-body text-ink/60">{t("twoFactorPrompt")}</p>

        <form onSubmit={handleVerifyTwoFactor} className="space-y-4">
          <input
            type="text"
            inputMode="numeric"
            autoComplete="one-time-code"
            autoFocus
            required
            value={otpCode}
            onChange={(event) => setOtpCode(event.target.value)}
            maxLength={20}
            className="w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-center text-h3 tracking-[0.3em] text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />

          {error && <p className="text-small text-error">{error}</p>}

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

  if (status === "error") {
    return (
      <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col items-center justify-center gap-4 px-6 py-16 text-center">
        <h1 className="text-h2 font-semibold text-primary">{t("socialLoginErrorTitle")}</h1>
        <p className="text-body text-ink/60">{error ?? t("socialLoginError")}</p>
        <Link href="/login" className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90">
          {t("backToLogin")}
        </Link>
      </div>
    );
  }

  return <ShabaLoader full showIndicator label={t("loading")} />;
}

export default function AuthCallbackPage() {
  return (
    <Suspense fallback={null}>
      <AuthCallbackContent />
    </Suspense>
  );
}

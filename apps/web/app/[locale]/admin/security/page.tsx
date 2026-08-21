"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import {
  confirmTwoFactorSetup,
  disableTwoFactor,
  getAdminMe,
  startTwoFactorSetup,
  type TwoFactorSetup,
} from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

type Stage = "loading" | "disabled" | "setting_up" | "confirmed_recovery_codes" | "enabled";

export default function AdminSecurityPage() {
  const t = useTranslations("Admin");

  const [stage, setStage] = useState<Stage>("loading");
  const [setup, setSetup] = useState<TwoFactorSetup | null>(null);
  const [code, setCode] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    getAdminMe()
      .then((user) => setStage(user.two_factor_enabled ? "enabled" : "disabled"))
      .catch(() => setStage("disabled"));
  }, []);

  async function handleStart() {
    setError(null);
    setBusy(true);
    try {
      const data = await startTwoFactorSetup();
      setSetup(data);
      setStage("setting_up");
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setBusy(false);
    }
  }

  async function handleConfirm(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const data = await confirmTwoFactorSetup(code);
      setRecoveryCodes(data.recovery_codes);
      setStage("confirmed_recovery_codes");
    } catch {
      setError(t("twoFactorCodeError"));
    } finally {
      setBusy(false);
    }
  }

  async function handleDisable(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await disableTwoFactor(password);
      setStage("disabled");
      setPassword("");
    } catch {
      setError(t("twoFactorDisableError"));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div>
      <h1 className="mb-2 text-h1 font-semibold text-primary">{t("securityTitle")}</h1>
      <p className="mb-6 text-small text-ink/60">{t("securitySubtitle")}</p>

      <div className="max-w-xl rounded-2xl bg-white p-6 shadow-soft">
        {stage === "loading" && <p className="text-body text-ink/50">{t("loading")}</p>}

        {stage === "disabled" && (
          <>
            <p className="mb-4 text-body text-ink/70">{t("twoFactorDisabledDescription")}</p>
            {error && <p className="mb-4 text-small text-error">{error}</p>}
            <button
              type="button"
              onClick={handleStart}
              disabled={busy}
              className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
            >
              {t("twoFactorEnable")}
            </button>
          </>
        )}

        {stage === "setting_up" && setup && (
          <form onSubmit={handleConfirm}>
            <p className="mb-3 text-body text-ink/70">{t("twoFactorScanOrEnter")}</p>
            <div className="mb-4 break-all rounded-lg bg-primary/5 p-3 font-mono text-small text-primary">
              {setup.secret}
            </div>
            <label className="mb-4 block">
              <span className="mb-1 block text-small font-medium text-ink/70">{t("twoFactorCodeField")}</span>
              <input
                type="text"
                inputMode="numeric"
                required
                value={code}
                onChange={(event) => setCode(event.target.value)}
                className={`${inputClass} text-center tracking-[0.3em]`}
                maxLength={6}
              />
            </label>
            {error && <p className="mb-4 text-small text-error">{error}</p>}
            <button
              type="submit"
              disabled={busy}
              className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
            >
              {t("twoFactorSubmit")}
            </button>
          </form>
        )}

        {stage === "confirmed_recovery_codes" && (
          <>
            <p className="mb-3 text-body font-semibold text-success">{t("twoFactorEnabledSuccess")}</p>
            <p className="mb-3 text-small text-ink/70">{t("twoFactorRecoveryCodesHint")}</p>
            <ul className="mb-4 grid grid-cols-2 gap-2 rounded-lg bg-primary/5 p-4 font-mono text-small text-primary">
              {recoveryCodes.map((rc) => (
                <li key={rc}>{rc}</li>
              ))}
            </ul>
            <button
              type="button"
              onClick={() => setStage("enabled")}
              className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
            >
              {t("twoFactorDone")}
            </button>
          </>
        )}

        {stage === "enabled" && (
          <form onSubmit={handleDisable}>
            <p className="mb-4 text-body font-semibold text-success">{t("twoFactorEnabledStatus")}</p>
            <label className="mb-4 block">
              <span className="mb-1 block text-small font-medium text-ink/70">{t("passwordField")}</span>
              <input
                type="password"
                required
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                className={inputClass}
              />
            </label>
            {error && <p className="mb-4 text-small text-error">{error}</p>}
            <button
              type="submit"
              disabled={busy}
              className="rounded-full border border-error/40 px-5 py-2.5 text-small font-semibold text-error hover:bg-error/5 disabled:opacity-50"
            >
              {t("twoFactorDisable")}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { AccountNav } from "@/components/customer/AccountNav";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import {
  ApiError,
  getSocialAccounts,
  getSocialLinkUrl,
  setInitialPassword,
  unlinkSocialAccount,
  type SocialAccountStatus,
} from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

const PROVIDER_LABEL_KEYS = {
  google: "continueWithGoogleShort",
  facebook: "continueWithFacebookShort",
  apple: "continueWithAppleShort",
} as const;

export default function SecurityPage() {
  const t = useTranslations("Auth");
  const tLoading = useTranslations("Loading");
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [accounts, setAccounts] = useState<SocialAccountStatus[]>([]);
  const [loadingAccounts, setLoadingAccounts] = useState(true);
  const [actionError, setActionError] = useState<string | null>(null);
  const [pendingProvider, setPendingProvider] = useState<string | null>(null);

  const [hasPassword, setHasPassword] = useState(true);
  const [newPassword, setNewPassword] = useState("");
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState("");
  const [passwordSaving, setPasswordSaving] = useState(false);
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [passwordSuccess, setPasswordSuccess] = useState(false);

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    // eslint-disable-next-line react-hooks/set-state-in-effect -- one-time init from the session-hydrated customer identity, not derived from a render value
    setHasPassword(Boolean(customer.has_password));

    getSocialAccounts()
      .then((data) => setAccounts(data.accounts))
      .finally(() => setLoadingAccounts(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer]);

  const connectedCount = accounts.filter((a) => a.connected).length;

  async function handleConnect(provider: SocialAccountStatus["provider"]) {
    setActionError(null);
    setPendingProvider(provider);

    try {
      const { redirect_url } = await getSocialLinkUrl(provider);
      window.location.assign(redirect_url);
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : t("saveError"));
      setPendingProvider(null);
    }
  }

  async function handleDisconnect(provider: SocialAccountStatus["provider"]) {
    setActionError(null);
    setPendingProvider(provider);

    try {
      await unlinkSocialAccount(provider);
      setAccounts((current) => current.map((a) => (a.provider === provider ? { ...a, connected: false, email: null } : a)));
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : t("saveError"));
    } finally {
      setPendingProvider(null);
    }
  }

  async function handleSetPassword(event: FormEvent) {
    event.preventDefault();
    setPasswordError(null);
    setPasswordSuccess(false);
    setPasswordSaving(true);

    try {
      await setInitialPassword({ password: newPassword, password_confirmation: newPasswordConfirmation });
      setHasPassword(true);
      setPasswordSuccess(true);
      setNewPassword("");
      setNewPasswordConfirmation("");
    } catch (err) {
      setPasswordError(err instanceof ApiError ? err.message : t("saveError"));
    } finally {
      setPasswordSaving(false);
    }
  }

  if (authLoading || !customer) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  return (
    <div className="mx-auto max-w-2xl px-6 py-10">
      <AccountNav />
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("securityTitle")}</h1>

      <div className="mb-10 rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="mb-1 text-h3 font-semibold text-ink">{t("connectedAccounts")}</h2>
        <p className="mb-4 text-caption text-ink/50">{t("connectedAccountsHint")}</p>

        {actionError && <p className="mb-4 text-small text-error">{actionError}</p>}

        {loadingAccounts ? (
          <ShabaLoader label={tLoading("label")} />
        ) : (
          <div className="space-y-2">
            {accounts.map((account) => (
              <div
                key={account.provider}
                className="flex items-center justify-between rounded-lg border border-primary/10 px-4 py-3"
              >
                <div>
                  <p className="text-small font-medium text-ink">{t(PROVIDER_LABEL_KEYS[account.provider])}</p>
                  {account.connected && account.email && <p className="text-caption text-ink/50">{account.email}</p>}
                </div>

                {account.connected ? (
                  <button
                    type="button"
                    disabled={pendingProvider === account.provider}
                    onClick={() => {
                      if (window.confirm(t("unlinkConfirm"))) handleDisconnect(account.provider);
                    }}
                    className="text-small font-medium text-error hover:underline disabled:opacity-50"
                  >
                    {t("disconnect")}
                  </button>
                ) : (
                  <button
                    type="button"
                    disabled={pendingProvider === account.provider}
                    onClick={() => handleConnect(account.provider)}
                    className="text-small font-medium text-primary hover:underline disabled:opacity-50"
                  >
                    {t("connect")}
                  </button>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      <form onSubmit={handleSetPassword} className="space-y-4 rounded-2xl bg-white p-6 shadow-soft">
        {hasPassword ? (
          <>
            <h2 className="text-h3 font-semibold text-ink">{t("changePasswordTitle")}</h2>
            <p className="text-caption text-ink/50">{t("goToAccountSettingsForPassword")}</p>
          </>
        ) : (
          <>
            <h2 className="text-h3 font-semibold text-ink">{t("setPasswordTitle")}</h2>
            <p className="text-caption text-ink/50">{t("setPasswordHint")}</p>

            <label className="block">
              <span className="mb-1 block text-small font-medium text-ink/70">{t("newPassword")}</span>
              <input
                type="password"
                required
                autoComplete="new-password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                className={inputClass}
              />
            </label>

            <label className="block">
              <span className="mb-1 block text-small font-medium text-ink/70">{t("confirmPassword")}</span>
              <input
                type="password"
                required
                autoComplete="new-password"
                value={newPasswordConfirmation}
                onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                className={inputClass}
              />
            </label>

            {passwordError && <p className="text-small text-error">{passwordError}</p>}
            {passwordSuccess && <p className="text-small text-success">{t("saveSuccess")}</p>}

            <button
              type="submit"
              disabled={passwordSaving}
              className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
            >
              {t("setPasswordSubmit")}
            </button>
          </>
        )}
      </form>

      {!hasPassword && connectedCount <= 1 && (
        <p className="mt-4 text-caption text-ink/50">{t("lastMethodHint")}</p>
      )}
    </div>
  );
}

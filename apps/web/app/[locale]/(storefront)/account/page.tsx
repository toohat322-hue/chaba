"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { AccountNav } from "@/components/customer/AccountNav";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import { ApiError, changePassword, updateProfile } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function AccountSettingsPage() {
  const t = useTranslations("Auth");
  const tLoading = useTranslations("Loading");
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading, logout } = useCustomerAuth();

  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [preferredLanguage, setPreferredLanguage] = useState<"ar" | "fr" | "en">("ar");
  const [profileSaving, setProfileSaving] = useState(false);
  const [profileError, setProfileError] = useState<string | null>(null);
  const [profileSuccess, setProfileSuccess] = useState(false);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState("");
  const [passwordSaving, setPasswordSaving] = useState(false);
  const [passwordError, setPasswordError] = useState<string | null>(null);

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    // eslint-disable-next-line react-hooks/set-state-in-effect -- prefilling the form from the session-hydrated customer identity, not deriving state from a render value
    setFullName(customer.full_name);
    setEmail(customer.email ?? "");
    setPreferredLanguage(customer.preferred_language as "ar" | "fr" | "en");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer]);

  async function handleProfileSubmit(event: FormEvent) {
    event.preventDefault();
    setProfileError(null);
    setProfileSuccess(false);
    setProfileSaving(true);

    try {
      await updateProfile({ full_name: fullName, email: email || null, preferred_language: preferredLanguage });
      setProfileSuccess(true);
    } catch (err) {
      setProfileError(err instanceof ApiError ? err.message : t("saveError"));
    } finally {
      setProfileSaving(false);
    }
  }

  async function handlePasswordSubmit(event: FormEvent) {
    event.preventDefault();
    setPasswordError(null);
    setPasswordSaving(true);

    try {
      await changePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: newPasswordConfirmation,
      });
      await logout();
      router.push("/login");
    } catch (err) {
      setPasswordError(err instanceof ApiError ? err.message : t("saveError"));
      setPasswordSaving(false);
    }
  }

  if (authLoading || !customer) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  return (
    <div className="mx-auto max-w-2xl px-6 py-10">
      <AccountNav />
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("accountSettings")}</h1>

      <form onSubmit={handleProfileSubmit} className="mb-10 space-y-4 rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="text-h3 font-semibold text-ink">{t("profileTitle")}</h2>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("fullName")}</span>
          <input value={fullName} onChange={(e) => setFullName(e.target.value)} required className={inputClass} />
        </label>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("emailOptional")}</span>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} className={inputClass} />
        </label>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("preferredLanguage")}</span>
          <select
            value={preferredLanguage}
            onChange={(e) => setPreferredLanguage(e.target.value as "ar" | "fr" | "en")}
            className={inputClass}
          >
            <option value="ar">العربية</option>
            <option value="fr">Français</option>
            <option value="en">English</option>
          </select>
        </label>

        {profileError && <p className="text-small text-error">{profileError}</p>}
        {profileSuccess && <p className="text-small text-success">{t("saveSuccess")}</p>}

        <button
          type="submit"
          disabled={profileSaving}
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("save")}
        </button>
      </form>

      <form onSubmit={handlePasswordSubmit} className="space-y-4 rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="text-h3 font-semibold text-ink">{t("changePasswordTitle")}</h2>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("currentPassword")}</span>
          <input
            type="password"
            required
            autoComplete="current-password"
            value={currentPassword}
            onChange={(e) => setCurrentPassword(e.target.value)}
            className={inputClass}
          />
        </label>

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

        <p className="text-caption text-ink/50">{t("changePasswordNote")}</p>

        {passwordError && <p className="text-small text-error">{passwordError}</p>}

        <button
          type="submit"
          disabled={passwordSaving}
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("changePasswordSubmit")}
        </button>
      </form>
    </div>
  );
}

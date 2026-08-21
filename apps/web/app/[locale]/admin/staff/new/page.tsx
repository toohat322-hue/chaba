"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { ApiError, createAdminStaff, getAdminRoles, type AdminRole } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function NewStaffPage() {
  const t = useTranslations("Admin");
  const tAuth = useTranslations("Auth");
  const router = useRouter();

  const [roles, setRoles] = useState<AdminRole[]>([]);
  const [fullName, setFullName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [roleId, setRoleId] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    getAdminRoles().then((data) => {
      setRoles(data.filter((role) => role.name !== "Super Admin"));
    });
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await createAdminStaff({
        full_name: fullName,
        phone,
        password,
        password_confirmation: passwordConfirmation,
        role_id: roleId,
      });
      router.push("/admin/staff");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="max-w-sm">
      <Link href="/admin/staff" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("back")}
      </Link>

      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("newStaff")}</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="text"
          required
          placeholder={t("name")}
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
        <select
          required
          value={roleId}
          onChange={(event) => setRoleId(event.target.value)}
          className={inputClass}
        >
          <option value="" disabled>
            {t("navRoles")}
          </option>
          {roles.map((role) => (
            <option key={role.id} value={role.id}>
              {role.name}
            </option>
          ))}
        </select>
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={t("passwordField")}
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          className={inputClass}
        />
        <input
          type="password"
          required
          autoComplete="new-password"
          placeholder={tAuth("confirmPassword")}
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
          {submitting ? t("loading") : t("save")}
        </button>
      </form>
    </div>
  );
}

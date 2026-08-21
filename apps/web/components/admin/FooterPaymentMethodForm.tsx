"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import type { AdminFooterPaymentMethod, FooterPaymentMethodPayload } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

const ICONS = ["cod", "cib", "edahabia", "visa", "mastercard", "applepay", "mada", "card"] as const;

export function FooterPaymentMethodForm({
  initial,
  onSubmit,
  onCancel,
  submitLabel,
}: {
  initial?: AdminFooterPaymentMethod;
  onSubmit: (payload: FooterPaymentMethodPayload) => Promise<void>;
  onCancel?: () => void;
  submitLabel: string;
}) {
  const t = useTranslations("Admin");

  const [nameAr, setNameAr] = useState(initial?.name.ar ?? "");
  const [nameFr, setNameFr] = useState(initial?.name.fr ?? "");
  const [nameEn, setNameEn] = useState(initial?.name.en ?? "");
  const [icon, setIcon] = useState(initial?.icon ?? ICONS[0]);
  const [isActive, setIsActive] = useState(initial?.is_active ?? true);
  const [sortOrder, setSortOrder] = useState(String(initial?.sort_order ?? 0));

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);

    try {
      await onSubmit({
        name_ar: nameAr,
        name_fr: nameFr,
        name_en: nameEn,
        icon,
        is_active: isActive,
        sort_order: Number(sortOrder),
      });
    } catch {
      setError(t("errorGeneric"));
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameAr")}</span>
        <input value={nameAr} onChange={(event) => setNameAr(event.target.value)} required dir="rtl" className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameFr")}</span>
        <input value={nameFr} onChange={(event) => setNameFr(event.target.value)} required className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameEn")}</span>
        <input value={nameEn} onChange={(event) => setNameEn(event.target.value)} required className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("icon")}</span>
        <select value={icon} onChange={(event) => setIcon(event.target.value)} className={inputClass}>
          {ICONS.map((slug) => (
            <option key={slug} value={slug}>
              {slug}
            </option>
          ))}
        </select>
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("sortOrder")}</span>
        <input type="number" value={sortOrder} onChange={(event) => setSortOrder(event.target.value)} className={inputClass} />
      </label>
      <label className="flex items-center gap-2 self-end text-small text-ink/80">
        <input type="checkbox" checked={isActive} onChange={(event) => setIsActive(event.target.checked)} />
        {t("active")}
      </label>

      {error && <p className="col-span-full text-small text-error">{error}</p>}

      <div className="col-span-full flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {submitLabel}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel} className="text-small text-ink/60 hover:text-ink">
            {t("cancel")}
          </button>
        )}
      </div>
    </form>
  );
}

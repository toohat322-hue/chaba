"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import type { AdminFooterFeature, FooterFeaturePayload } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

const ICONS = ["truck", "shield", "return", "badge", "clock", "gift", "star", "lock"] as const;

export function FooterFeatureForm({
  initial,
  onSubmit,
  onCancel,
  submitLabel,
}: {
  initial?: AdminFooterFeature;
  onSubmit: (payload: FooterFeaturePayload) => Promise<void>;
  onCancel?: () => void;
  submitLabel: string;
}) {
  const t = useTranslations("Admin");

  const [icon, setIcon] = useState(initial?.icon ?? ICONS[0]);
  const [titleAr, setTitleAr] = useState(initial?.title.ar ?? "");
  const [titleFr, setTitleFr] = useState(initial?.title.fr ?? "");
  const [titleEn, setTitleEn] = useState(initial?.title.en ?? "");
  const [descriptionAr, setDescriptionAr] = useState(initial?.description.ar ?? "");
  const [descriptionFr, setDescriptionFr] = useState(initial?.description.fr ?? "");
  const [descriptionEn, setDescriptionEn] = useState(initial?.description.en ?? "");
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
        icon,
        title_ar: titleAr,
        title_fr: titleFr,
        title_en: titleEn,
        description_ar: descriptionAr || null,
        description_fr: descriptionFr || null,
        description_en: descriptionEn || null,
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

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameAr")}</span>
        <input value={titleAr} onChange={(event) => setTitleAr(event.target.value)} required dir="rtl" className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameFr")}</span>
        <input value={titleFr} onChange={(event) => setTitleFr(event.target.value)} required className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("nameEn")}</span>
        <input value={titleEn} onChange={(event) => setTitleEn(event.target.value)} required className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("descriptionAr")}</span>
        <input value={descriptionAr} onChange={(event) => setDescriptionAr(event.target.value)} dir="rtl" className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("descriptionFr")}</span>
        <input value={descriptionFr} onChange={(event) => setDescriptionFr(event.target.value)} className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("descriptionEn")}</span>
        <input value={descriptionEn} onChange={(event) => setDescriptionEn(event.target.value)} className={inputClass} />
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

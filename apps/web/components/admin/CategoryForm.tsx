"use client";

import { useEffect, useState, type FormEvent, type ReactNode } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import {
  createAdminCategory,
  getAdminCategories,
  updateAdminCategory,
  type AdminCategory,
} from "@/lib/api";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-small font-medium text-ink/70">{label}</span>
      {children}
    </label>
  );
}

export function CategoryForm({ categoryId, initial }: { categoryId?: string; initial?: AdminCategory }) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const router = useRouter();

  const [parents, setParents] = useState<AdminCategory[]>([]);
  const [parentId, setParentId] = useState(initial?.parent_id ?? "");
  const [nameAr, setNameAr] = useState(initial?.name.ar ?? "");
  const [nameFr, setNameFr] = useState(initial?.name.fr ?? "");
  const [nameEn, setNameEn] = useState(initial?.name.en ?? "");
  const [sortOrder, setSortOrder] = useState(String(initial?.sort_order ?? 0));
  const [isActive, setIsActive] = useState(initial?.is_active ?? true);
  const [seoTitle, setSeoTitle] = useState(initial?.seo_title ?? "");
  const [seoDescription, setSeoDescription] = useState(initial?.seo_description ?? "");

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getAdminCategories()
      .then((all) => setParents(all.filter((category) => category.id !== categoryId)))
      .catch(() => setParents([]));
  }, [categoryId]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);

    const payload = {
      parent_id: parentId || null,
      name_ar: nameAr,
      name_fr: nameFr,
      name_en: nameEn,
      sort_order: Number(sortOrder),
      is_active: isActive,
      seo_title: seoTitle || undefined,
      seo_description: seoDescription || undefined,
    };

    try {
      if (categoryId) {
        await updateAdminCategory(categoryId, payload);
      } else {
        await createAdminCategory(payload);
      }
      router.push("/admin/categories");
    } catch {
      setError(t("errorGeneric"));
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-5">
      <Field label={t("parentCategory")}>
        <select value={parentId} onChange={(event) => setParentId(event.target.value)} className={inputClass}>
          <option value="">{t("noParent")}</option>
          {parents.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name[locale]}
            </option>
          ))}
        </select>
      </Field>

      <Field label={t("nameAr")}>
        <input
          value={nameAr}
          onChange={(event) => setNameAr(event.target.value)}
          required
          dir="rtl"
          className={inputClass}
        />
      </Field>
      <Field label={t("nameFr")}>
        <input value={nameFr} onChange={(event) => setNameFr(event.target.value)} required className={inputClass} />
      </Field>
      <Field label={t("nameEn")}>
        <input value={nameEn} onChange={(event) => setNameEn(event.target.value)} required className={inputClass} />
      </Field>

      <Field label={t("sortOrder")}>
        <input
          type="number"
          value={sortOrder}
          onChange={(event) => setSortOrder(event.target.value)}
          className={inputClass}
        />
      </Field>

      <Field label={t("seoTitle")}>
        <input value={seoTitle} onChange={(event) => setSeoTitle(event.target.value)} className={inputClass} />
      </Field>
      <Field label={t("seoDescription")}>
        <textarea
          value={seoDescription}
          onChange={(event) => setSeoDescription(event.target.value)}
          rows={2}
          className={inputClass}
        />
      </Field>

      <label className="flex items-center gap-2 text-small text-ink/80">
        <input type="checkbox" checked={isActive} onChange={(event) => setIsActive(event.target.checked)} />
        {t("active")}
      </label>

      {error && <p className="text-small text-error">{error}</p>}

      <div className="flex items-center gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("save")}
        </button>
        <Link href="/admin/categories" className="text-small text-ink/60 hover:text-ink">
          {t("cancel")}
        </Link>
      </div>
    </form>
  );
}

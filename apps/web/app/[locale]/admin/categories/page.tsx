"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { ApiError, deleteAdminCategory, getAdminCategories, type AdminCategory } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export default function AdminCategoriesPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminCategories()
      .then(setItems)
      .finally(() => setLoading(false));
  }

  // eslint-disable-next-line react-hooks/set-state-in-effect -- initial fetch on mount, loading flag starts true and is only ever cleared from the promise callback
  useEffect(load, []);

  async function handleDelete(id: string) {
    if (!window.confirm(t("confirmDelete"))) return;
    setError(null);
    try {
      await deleteAdminCategory(id);
      load();
    } catch (err) {
      setError(
        err instanceof ApiError && err.code === "category_has_products"
          ? t("categoryHasProducts")
          : t("errorGeneric"),
      );
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("categoriesTitle")}</h1>
        <Link
          href="/admin/categories/new"
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
        >
          {t("newCategory")}
        </Link>
      </div>

      {error && <p className="mb-4 text-small text-error">{error}</p>}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
          <table className="w-full text-start text-small">
            <thead>
              <tr className="border-b border-primary/10 text-ink/60">
                <th className="px-4 py-3 text-start font-medium">{t("name")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("productCount")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((category) => (
                <tr key={category.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3 text-ink">{category.name[locale]}</td>
                  <td className="px-4 py-3 text-ink/70">{category.product_count}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-1 text-caption font-medium ${
                        category.is_active ? "bg-success/10 text-success" : "bg-ink/10 text-ink/60"
                      }`}
                    >
                      {category.is_active ? t("active") : t("inactive")}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <Link href={`/admin/categories/${category.id}/edit`} className="text-primary hover:underline">
                        {t("edit")}
                      </Link>
                      <button
                        type="button"
                        onClick={() => handleDelete(category.id)}
                        className="text-error hover:underline"
                      >
                        {t("delete")}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

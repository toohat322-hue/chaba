"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getAuditLogs, type AuditLogEntry, type ProductListMeta } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

// Per-locale label for each action string the backend can emit (see
// App\Support\AuditLogger call sites) — same small self-contained map
// convention as lib/currency.ts's UNIT_LABEL, since this is the only place
// that needs it. An action not in the map falls back to the raw string
// rather than erroring, so a future new action never breaks this page.
const ACTION_LABELS: Record<Locale, Record<string, string>> = {
  ar: {
    "product.created": "إنشاء منتج",
    "product.updated": "تعديل منتج",
    "product.archived": "أرشفة منتج",
    "variant.created": "إضافة حجم",
    "variant.updated": "تعديل حجم",
    "variant.deleted": "حذف حجم",
    "category.created": "إنشاء فئة",
    "category.updated": "تعديل فئة",
    "category.deleted": "حذف فئة",
    "order.status_changed": "تغيير حالة طلب",
    "coupon.created": "إنشاء كود خصم",
    "coupon.updated": "تعديل كود خصم",
    "coupon.deactivated": "إلغاء تفعيل كود خصم",
    "role.permissions_updated": "تعديل صلاحيات دور",
    "staff.created": "إضافة موظف",
    "staff.updated": "تعديل موظف",
    "delivery_fee.created": "إضافة رسوم توصيل",
    "delivery_fee.updated": "تعديل رسوم توصيل",
    "settings.updated": "تعديل إعدادات المتجر",
    "inventory.adjusted": "تعديل المخزون",
  },
  fr: {
    "product.created": "Création de produit",
    "product.updated": "Modification de produit",
    "product.archived": "Archivage de produit",
    "variant.created": "Ajout de taille",
    "variant.updated": "Modification de taille",
    "variant.deleted": "Suppression de taille",
    "category.created": "Création de catégorie",
    "category.updated": "Modification de catégorie",
    "category.deleted": "Suppression de catégorie",
    "order.status_changed": "Changement de statut de commande",
    "coupon.created": "Création de code promo",
    "coupon.updated": "Modification de code promo",
    "coupon.deactivated": "Désactivation de code promo",
    "role.permissions_updated": "Modification des permissions d'un rôle",
    "staff.created": "Ajout d'un employé",
    "staff.updated": "Modification d'un employé",
    "delivery_fee.created": "Ajout de frais de livraison",
    "delivery_fee.updated": "Modification de frais de livraison",
    "settings.updated": "Modification des paramètres du magasin",
    "inventory.adjusted": "Ajustement de stock",
  },
  en: {
    "product.created": "Product created",
    "product.updated": "Product updated",
    "product.archived": "Product archived",
    "variant.created": "Size added",
    "variant.updated": "Size updated",
    "variant.deleted": "Size deleted",
    "category.created": "Category created",
    "category.updated": "Category updated",
    "category.deleted": "Category deleted",
    "order.status_changed": "Order status changed",
    "coupon.created": "Coupon created",
    "coupon.updated": "Coupon updated",
    "coupon.deactivated": "Coupon deactivated",
    "role.permissions_updated": "Role permissions updated",
    "staff.created": "Staff member added",
    "staff.updated": "Staff member updated",
    "delivery_fee.created": "Delivery fee added",
    "delivery_fee.updated": "Delivery fee updated",
    "settings.updated": "Store settings updated",
    "inventory.adjusted": "Inventory adjusted",
  },
};

function actionLabel(action: string, locale: Locale): string {
  return ACTION_LABELS[locale][action] ?? action;
}

function ChangesList({ log }: { log: AuditLogEntry }) {
  const t = useTranslations("Admin");
  const fields = Object.keys(log.after ?? {});

  if (fields.length === 0) {
    return <span className="text-ink/40">{t("noChanges")}</span>;
  }

  return (
    <ul className="space-y-0.5">
      {fields.map((field) => (
        <li key={field} className="text-caption text-ink/70">
          <span className="font-medium text-ink">{field}</span>: {String(log.before?.[field] ?? "—")} →{" "}
          {String(log.after?.[field] ?? "—")}
        </li>
      ))}
    </ul>
  );
}

export default function AdminAuditLogPage() {
  const t = useTranslations("Admin");
  const tCategory = useTranslations("Category");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AuditLogEntry[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [action, setAction] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [page, setPage] = useState(1);

  async function load(actionFilter: string, from: string, to: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAuditLogs({
        action: actionFilter || undefined,
        date_from: from || undefined,
        date_to: to || undefined,
        page: pageNum,
      });
      setItems(res.items);
      setMeta(res.meta);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(action, dateFrom, dateTo, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleFilter(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(action, dateFrom, dateTo, 1);
  }

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("auditLogTitle")}</h1>

      <form onSubmit={handleFilter} className="mb-4 flex flex-wrap items-end gap-3">
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("auditAction")}</span>
          <input
            type="text"
            value={action}
            onChange={(event) => setAction(event.target.value)}
            placeholder={t("actionFilterPlaceholder")}
            className="w-48 rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("dateFrom")}</span>
          <input
            type="date"
            value={dateFrom}
            onChange={(event) => setDateFrom(event.target.value)}
            className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("dateTo")}</span>
          <input
            type="date"
            value={dateTo}
            onChange={(event) => setDateTo(event.target.value)}
            className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>
        <button
          type="submit"
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
        >
          {t("apply")}
        </button>
      </form>

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
          <table className="w-full text-start text-small">
            <thead>
              <tr className="border-b border-primary/10 text-ink/60">
                <th className="px-4 py-3 text-start font-medium">{t("date")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actor")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("auditAction")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("item")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("changes")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((log) => (
                <tr key={log.id} className="border-b border-primary/5 last:border-0">
                  <td className="whitespace-nowrap px-4 py-3 text-ink/70">
                    {new Date(log.created_at).toLocaleString(locale)}
                  </td>
                  <td className="px-4 py-3 text-ink">{log.actor_name}</td>
                  <td className="px-4 py-3 text-ink/80">{actionLabel(log.action, locale)}</td>
                  <td className="px-4 py-3 text-ink/70">{log.subject_label}</td>
                  <td className="px-4 py-3">
                    <ChangesList log={log} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {meta && meta.last_page > 1 && (
        <nav className="mt-6 flex items-center justify-center gap-4 text-small">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
            className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40 disabled:opacity-30"
          >
            {tCategory("previousPage")}
          </button>
          <span className="text-ink/60">{tCategory("pageOf", { current: meta.current_page, total: meta.last_page })}</span>
          <button
            type="button"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
            className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40 disabled:opacity-30"
          >
            {tCategory("nextPage")}
          </button>
        </nav>
      )}
    </div>
  );
}

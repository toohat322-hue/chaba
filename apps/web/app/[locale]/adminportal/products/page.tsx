"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { archiveAdminProduct, getAdminProducts, type AdminProduct, type ProductListMeta } from "@/lib/api";
import { formatPrice } from "@/lib/currency";
import { ExportCsvButton } from "@/components/admin/ExportCsvButton";

type Locale = "ar" | "fr" | "en";

export default function AdminProductsPage() {
  const t = useTranslations("Admin");
  const tCategory = useTranslations("Category");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminProduct[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);

  async function load(query: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAdminProducts({ q: query || undefined, page: pageNum });
      setItems(res.items);
      setMeta(res.meta);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(q, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleSearch(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(q, 1);
  }

  async function handleArchive(id: string) {
    if (!window.confirm(t("confirmArchive"))) return;
    await archiveAdminProduct(id);
    load(q, page);
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-h1 font-semibold text-primary">{t("productsTitle")}</h1>
        <div className="flex items-center gap-3">
          <ExportCsvButton path={`/adminportal/products/export?${new URLSearchParams(q ? { q } : {}).toString()}`} filename="products.csv" />
          <Link
            href="/adminportal/products/new"
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("newProduct")}
          </Link>
        </div>
      </div>

      <form onSubmit={handleSearch} className="mb-4">
        <input
          type="search"
          value={q}
          onChange={(event) => setQ(event.target.value)}
          placeholder={t("searchPlaceholder")}
          className="w-full max-w-sm rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        />
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
                <th className="px-4 py-3 text-start font-medium">{t("name")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("category")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("price")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("stock")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((product) => (
                <tr key={product.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3 text-ink">{product.name[locale]}</td>
                  <td className="px-4 py-3 text-ink/70">{product.category?.name[locale] ?? "—"}</td>
                  <td className="px-4 py-3 text-ink/70">{formatPrice(product.base_price, locale)}</td>
                  <td className="px-4 py-3 text-ink/70">{formatStock(product.variants)}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={product.status} label={statusLabel(product.status, t)} />
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <Link href={`/adminportal/products/${product.id}/edit`} className="text-primary hover:underline">
                        {t("edit")}
                      </Link>
                      {product.status !== "archived" && (
                        <button
                          type="button"
                          onClick={() => handleArchive(product.id)}
                          className="text-error hover:underline"
                        >
                          {t("archive")}
                        </button>
                      )}
                    </div>
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

function formatStock(variants: AdminProduct["variants"]): string {
  if (variants.length === 0) return "—";
  const total = variants.reduce((sum, variant) => sum + variant.available_quantity, 0);
  return variants.length > 1 ? `${total} (×${variants.length})` : String(total);
}

function statusLabel(status: AdminProduct["status"], t: (key: string) => string): string {
  if (status === "draft") return t("statusDraft");
  if (status === "archived") return t("archived");
  return t("active");
}

function StatusBadge({ status, label }: { status: AdminProduct["status"]; label: string }) {
  const colors =
    status === "active"
      ? "bg-success/10 text-success"
      : status === "archived"
        ? "bg-error/10 text-error"
        : "bg-warning/10 text-warning";

  return <span className={`rounded-full px-2.5 py-1 text-caption font-medium ${colors}`}>{label}</span>;
}

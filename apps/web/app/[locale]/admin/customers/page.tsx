"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getAdminCustomers, type AdminCustomer, type ProductListMeta } from "@/lib/api";
import { ExportCsvButton } from "@/components/admin/ExportCsvButton";

type Locale = "ar" | "fr" | "en";

export default function AdminCustomersPage() {
  const t = useTranslations("Admin");
  const tCategory = useTranslations("Category");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminCustomer[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);

  async function load(query: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAdminCustomers({ q: query || undefined, page: pageNum });
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

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-h1 font-semibold text-primary">{t("customersTitle")}</h1>
        <ExportCsvButton path={`/admin/customers/export?${new URLSearchParams(q ? { q } : {}).toString()}`} filename="customers.csv" />
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
                <th className="px-4 py-3 text-start font-medium">{t("phone")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("email")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("joined")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((customer) => (
                <tr key={customer.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3">
                    <Link href={`/admin/customers/${customer.id}`} className="text-primary hover:underline">
                      {customer.full_name}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-ink/70">{customer.phone}</td>
                  <td className="px-4 py-3 text-ink/70">{customer.email ?? "—"}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-1 text-caption font-medium ${
                        customer.status === "active" ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {customer.status === "active" ? t("active") : t("blocked")}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-ink/70">
                    {new Date(customer.created_at).toLocaleDateString(locale)}
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

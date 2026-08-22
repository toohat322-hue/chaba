"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getAdminOrders, type Order, type OrderStatus, type ProductListMeta } from "@/lib/api";
import { formatPrice } from "@/lib/currency";
import { ExportCsvButton } from "@/components/admin/ExportCsvButton";

type Locale = "ar" | "fr" | "en";

const STATUSES: OrderStatus[] = [
  "pending", "confirmed", "processing", "ready_to_ship", "shipped",
  "out_for_delivery", "delivered", "cancelled", "failed", "returned",
  "refunded", "partially_refunded",
];

export default function AdminOrdersPage() {
  const t = useTranslations("Admin");
  const tCategory = useTranslations("Category");
  const tStatus = useTranslations("OrderStatus");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<Order[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);

  async function load(query: string, statusFilter: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAdminOrders({ q: query || undefined, status: statusFilter || undefined, page: pageNum });
      setItems(res.items);
      setMeta(res.meta);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(q, status, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleFilter(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(q, status, 1);
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-h1 font-semibold text-primary">{t("ordersTitle")}</h1>
        <ExportCsvButton path={`/adminportal/orders/export?${new URLSearchParams({ ...(q ? { q } : {}), ...(status ? { status } : {}) }).toString()}`} filename="orders.csv" />
      </div>

      <form onSubmit={handleFilter} className="mb-4 flex flex-wrap gap-3">
        <input
          type="search"
          value={q}
          onChange={(event) => setQ(event.target.value)}
          placeholder={t("searchPlaceholder")}
          className="w-full max-w-sm rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        />
        <select
          value={status}
          onChange={(event) => setStatus(event.target.value)}
          className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
          <option value="">{t("allStatuses")}</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {tStatus(s)}
            </option>
          ))}
        </select>
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
                <th className="px-4 py-3 text-start font-medium">{t("orderNumber")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("customer")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("total")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("joined")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((order) => (
                <tr key={order.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3 text-ink">{order.order_number}</td>
                  <td className="px-4 py-3 text-ink/70">{order.customer.name}</td>
                  <td className="px-4 py-3 text-ink/70">{formatPrice(order.grand_total, locale)}</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-primary/10 px-2.5 py-1 text-caption font-medium text-primary">
                      {tStatus(order.order_status)}
                    </span>
                    {order.payment_method === "whatsapp" && (
                      <span className="ms-2 rounded-full bg-[#20B858]/10 px-2.5 py-1 text-caption font-medium text-[#20B858]">
                        {t("whatsappBadge")}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-ink/70">{new Date(order.created_at).toLocaleDateString(locale)}</td>
                  <td className="px-4 py-3">
                    <Link href={`/adminportal/orders/${order.id}`} className="text-primary hover:underline">
                      {t("viewOrder")}
                    </Link>
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

"use client";

import { useTranslations } from "next-intl";
import type { AdminDashboardAnalytics } from "@/lib/api";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

export function SalesTrendChart({
  data,
  locale,
}: {
  data: AdminDashboardAnalytics["sales_last_30_days"];
  locale: Locale;
}) {
  const t = useTranslations("Admin");
  const max = Math.max(1, ...data.map((d) => d.total));

  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <p className="mb-4 text-small font-semibold text-ink">{t("salesLast30Days")}</p>
      <div className="flex h-40 items-end gap-1" role="img" aria-label={t("salesLast30Days")}>
        {data.map((point) => (
          <div key={point.date} className="group relative h-full flex-1">
            <div
              className="absolute bottom-0 w-full rounded-t bg-accent/70 transition-colors group-hover:bg-accent"
              style={{ height: `${Math.max(2, (point.total / max) * 100)}%` }}
            />
            <div className="pointer-events-none absolute bottom-full start-1/2 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-primary px-2 py-1 text-caption text-background group-hover:block">
              {new Date(point.date).toLocaleDateString(locale, { month: "short", day: "numeric" })}: {formatPrice(point.total, locale)}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export function TopProductsChart({ data }: { data: AdminDashboardAnalytics["top_products"] }) {
  const t = useTranslations("Admin");
  const max = Math.max(1, ...data.map((d) => d.quantity_sold));

  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <p className="mb-4 text-small font-semibold text-ink">{t("topProducts")}</p>
      {data.length === 0 ? (
        <p className="text-small text-ink/50">{t("noResults")}</p>
      ) : (
        <ul className="space-y-3">
          {data.map((item) => (
            <li key={item.name}>
              <div className="mb-1 flex items-center justify-between text-small">
                <span className="text-ink">{item.name}</span>
                <span className="text-ink/60">{item.quantity_sold}</span>
              </div>
              <div className="h-2 overflow-hidden rounded-full bg-primary/10">
                <div
                  className="h-full rounded-full bg-primary"
                  style={{ width: `${Math.max(4, (item.quantity_sold / max) * 100)}%` }}
                />
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export function OrdersByStatusChart({ data }: { data: AdminDashboardAnalytics["orders_by_status"] }) {
  const t = useTranslations("Admin");
  const tStatus = useTranslations("OrderStatus");
  const total = Math.max(1, data.reduce((sum, d) => sum + d.count, 0));

  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <p className="mb-4 text-small font-semibold text-ink">{t("ordersByStatus")}</p>
      {data.length === 0 ? (
        <p className="text-small text-ink/50">{t("noResults")}</p>
      ) : (
        <ul className="space-y-3">
          {data.map((item) => (
            <li key={item.status}>
              <div className="mb-1 flex items-center justify-between text-small">
                <span className="text-ink">{tStatus(item.status)}</span>
                <span className="text-ink/60">{item.count}</span>
              </div>
              <div className="h-2 overflow-hidden rounded-full bg-primary/10">
                <div className="h-full rounded-full bg-accent" style={{ width: `${(item.count / total) * 100}%` }} />
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

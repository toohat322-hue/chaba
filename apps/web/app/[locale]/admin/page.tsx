"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getAdminDashboard, getAdminDashboardAnalytics, type AdminDashboardAnalytics } from "@/lib/api";
import { formatPrice } from "@/lib/currency";
import { SalesTrendChart, TopProductsChart, OrdersByStatusChart } from "@/components/admin/DashboardCharts";

type Dashboard = Awaited<ReturnType<typeof getAdminDashboard>>;
type Locale = "ar" | "fr" | "en";

export default function AdminDashboardPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [data, setData] = useState<Dashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [analytics, setAnalytics] = useState<AdminDashboardAnalytics | null>(null);

  useEffect(() => {
    getAdminDashboard()
      .then(setData)
      .catch(() => setData(null))
      .finally(() => setLoading(false));
    getAdminDashboardAnalytics()
      .then(setAnalytics)
      .catch(() => setAnalytics(null));
  }, []);

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("dashboardTitle")}</h1>

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <MetricCard label={t("totalProducts")} value={data?.total_products} />
          <MetricCard label={t("totalCategories")} value={data?.total_categories} />
          <MetricCard label={t("totalCustomers")} value={data?.total_customers} />
          <MetricCard
            label={t("lowStock")}
            value={data?.low_stock_count}
            warn={!!data && data.low_stock_count > 0}
          />

          {data?.orders_available && (
            <>
              <MetricCard label={t("totalOrders")} value={data.total_orders ?? undefined} />
              <MetricCard
                label={t("pendingOrders")}
                value={data.pending_orders ?? undefined}
                warn={!!data.pending_orders}
              />
              <MetricCard
                label={t("totalSales")}
                value={data.total_sales != null ? formatPrice(data.total_sales, locale) : undefined}
              />
            </>
          )}
        </div>
      )}

      {analytics && (
        <div className="mt-8 grid gap-4 lg:grid-cols-3">
          <div className="lg:col-span-3">
            <SalesTrendChart data={analytics.sales_last_30_days} locale={locale} />
          </div>
          <TopProductsChart data={analytics.top_products} />
          <OrdersByStatusChart data={analytics.orders_by_status} />
        </div>
      )}
    </div>
  );
}

function MetricCard({ label, value, warn }: { label: string; value: number | string | undefined; warn?: boolean }) {
  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <p className="text-small text-ink/60">{label}</p>
      <p className={`mt-2 text-display font-semibold ${warn ? "text-warning" : "text-primary"}`}>
        {value ?? "—"}
      </p>
    </div>
  );
}

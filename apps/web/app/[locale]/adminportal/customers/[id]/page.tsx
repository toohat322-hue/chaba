"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getAdminCustomer, updateAdminCustomerStatus, type AdminCustomer } from "@/lib/api";

type Locale = "ar" | "fr" | "en";
type CustomerDetail = AdminCustomer & { orders_available: false; orders: [] };

export default function AdminCustomerDetailPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const params = useParams<{ id: string }>();
  const customerId = params.id;

  const [customer, setCustomer] = useState<CustomerDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  function load() {
    setLoading(true);
    getAdminCustomer(customerId)
      .then(setCustomer)
      .finally(() => setLoading(false));
  }

  // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/customerId change, loading flag starts true and is only ever cleared from the promise callback
  useEffect(load, [customerId]);

  async function toggleStatus() {
    if (!customer) return;
    const nextStatus = customer.status === "active" ? "blocked" : "active";
    if (nextStatus === "blocked" && !window.confirm(t("confirmArchive"))) return;

    setUpdating(true);
    try {
      const updated = await updateAdminCustomerStatus(customer.id, nextStatus);
      setCustomer({ ...customer, ...updated });
    } finally {
      setUpdating(false);
    }
  }

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (!customer) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  return (
    <div className="max-w-xl">
      <Link href="/adminportal/customers" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("back")}
      </Link>

      <h1 className="mb-6 text-h1 font-semibold text-primary">{customer.full_name}</h1>

      <div className="mb-6 space-y-2 rounded-2xl bg-white p-6 shadow-soft text-small">
        <p>
          <span className="text-ink/60">{t("phone")}: </span>
          <span className="text-ink">{customer.phone}</span>
        </p>
        <p>
          <span className="text-ink/60">{t("email")}: </span>
          <span className="text-ink">{customer.email ?? "—"}</span>
        </p>
        <p>
          <span className="text-ink/60">{t("joined")}: </span>
          <span className="text-ink">{new Date(customer.created_at).toLocaleDateString(locale)}</span>
        </p>
        <p>
          <span className="text-ink/60">{t("status")}: </span>
          <span className={customer.status === "active" ? "text-success" : "text-error"}>
            {customer.status === "active" ? t("active") : t("blocked")}
          </span>
        </p>
      </div>

      <button
        type="button"
        onClick={toggleStatus}
        disabled={updating}
        className={`mb-8 rounded-full px-6 py-3 text-body font-semibold text-background disabled:opacity-50 ${
          customer.status === "active" ? "bg-error hover:bg-error/90" : "bg-success hover:bg-success/90"
        }`}
      >
        {customer.status === "active" ? t("block") : t("unblock")}
      </button>

      <div>
        <h2 className="mb-3 text-h3 font-semibold text-ink">{t("customerOrders")}</h2>
        <p className="rounded-2xl border border-dashed border-primary/20 bg-white/50 p-6 text-small text-ink/50">
          {t("ordersNotAvailable")}
        </p>
      </div>
    </div>
  );
}

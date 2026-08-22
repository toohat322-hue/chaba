"use client";

import { Fragment, useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import {
  getAdminPayments,
  reconcileAdminPayment,
  type AdminPayment,
  type ProductListMeta,
  type ReconciliationStatus,
} from "@/lib/api";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

const RECONCILIATION_BADGE: Record<ReconciliationStatus, string> = {
  unreconciled: "bg-warning/10 text-warning",
  reconciled: "bg-success/10 text-success",
  disputed: "bg-error/10 text-error",
};

export default function AdminPaymentsPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminPayment[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [provider, setProvider] = useState("");
  const [reconciliationStatus, setReconciliationStatus] = useState("");
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const [activeId, setActiveId] = useState<string | null>(null);
  const [notes, setNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function load(providerFilter: string, reconFilter: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAdminPayments({
        provider: providerFilter || undefined,
        reconciliation_status: reconFilter || undefined,
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
    load(provider, reconciliationStatus, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleFilter(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(provider, reconciliationStatus, 1);
  }

  function toggleReconcile(payment: AdminPayment) {
    setError(null);
    if (activeId === payment.id) {
      setActiveId(null);
      return;
    }
    setActiveId(payment.id);
    setNotes(payment.reconciliation_notes ?? "");
  }

  async function handleReconcile(id: string, status: ReconciliationStatus) {
    setSubmitting(true);
    setError(null);
    try {
      await reconcileAdminPayment(id, { reconciliation_status: status, notes: notes || undefined });
      setActiveId(null);
      load(provider, reconciliationStatus, page);
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("paymentsTitle")}</h1>

      <form onSubmit={handleFilter} className="mb-4 flex flex-wrap gap-3">
        <select
          value={provider}
          onChange={(event) => setProvider(event.target.value)}
          className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
          <option value="">{t("allProviders")}</option>
          <option value="cod">COD</option>
          <option value="cib">CIB</option>
          <option value="edahabia">Edahabia</option>
        </select>
        <select
          value={reconciliationStatus}
          onChange={(event) => setReconciliationStatus(event.target.value)}
          className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
          <option value="">{t("allStatuses")}</option>
          <option value="unreconciled">{t("unreconciled")}</option>
          <option value="reconciled">{t("reconciled")}</option>
          <option value="disputed">{t("disputed")}</option>
        </select>
        <button
          type="submit"
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
        >
          {t("apply")}
        </button>
      </form>

      {error && <p className="mb-4 text-small text-error">{error}</p>}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
          <table className="w-full text-small">
            <thead>
              <tr className="border-b border-primary/10 text-caption text-ink/50">
                <th className="px-4 py-3 text-start font-medium">{t("orderNumber")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("provider")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("price")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("reconciliationStatus")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((payment) => (
                <Fragment key={payment.id}>
                  <tr className="border-b border-primary/5 last:border-b-0">
                    <td className="px-4 py-3 text-ink">{payment.order_number ?? "—"}</td>
                    <td className="px-4 py-3 text-ink/70">{payment.provider.toUpperCase()}</td>
                    <td className="px-4 py-3 text-ink">{formatPrice(payment.amount, locale)}</td>
                    <td className="px-4 py-3 text-ink/70">{payment.status}</td>
                    <td className="px-4 py-3">
                      <span
                        className={`rounded-full px-2.5 py-1 text-caption font-medium ${RECONCILIATION_BADGE[payment.reconciliation_status]}`}
                      >
                        {t(payment.reconciliation_status)}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <button
                        type="button"
                        onClick={() => toggleReconcile(payment)}
                        className="text-small font-medium text-primary hover:underline"
                      >
                        {t("reconcile")}
                      </button>
                    </td>
                  </tr>
                  {activeId === payment.id && (
                    <tr className="border-b border-primary/5 bg-primary/5">
                      <td colSpan={6} className="px-4 py-4">
                        <textarea
                          value={notes}
                          onChange={(event) => setNotes(event.target.value)}
                          placeholder={t("noteOptional")}
                          rows={2}
                          className="mb-3 w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
                        />
                        <div className="flex flex-wrap gap-2">
                          <button
                            type="button"
                            disabled={submitting}
                            onClick={() => handleReconcile(payment.id, "reconciled")}
                            className="rounded-full bg-success/10 px-4 py-1.5 text-caption font-semibold text-success hover:bg-success/20 disabled:opacity-50"
                          >
                            {t("markReconciled")}
                          </button>
                          <button
                            type="button"
                            disabled={submitting}
                            onClick={() => handleReconcile(payment.id, "disputed")}
                            className="rounded-full bg-error/10 px-4 py-1.5 text-caption font-semibold text-error hover:bg-error/20 disabled:opacity-50"
                          >
                            {t("markDisputed")}
                          </button>
                          <button
                            type="button"
                            disabled={submitting}
                            onClick={() => setActiveId(null)}
                            className="rounded-full px-4 py-1.5 text-caption font-medium text-ink/60 hover:bg-primary/5"
                          >
                            {t("cancel")}
                          </button>
                        </div>
                      </td>
                    </tr>
                  )}
                </Fragment>
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
            ←
          </button>
          <span className="text-ink/60">
            {page} / {meta.last_page}
          </span>
          <button
            type="button"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
            className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40 disabled:opacity-30"
          >
            →
          </button>
        </nav>
      )}
    </div>
  );
}

"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import {
  createAdminFooterPaymentMethod,
  deleteAdminFooterPaymentMethod,
  getAdminFooterPaymentMethods,
  updateAdminFooterPaymentMethod,
  type AdminFooterPaymentMethod,
  type FooterPaymentMethodPayload,
} from "@/lib/api";
import { FooterPaymentMethodForm } from "@/components/admin/FooterPaymentMethodForm";

type Locale = "ar" | "fr" | "en";

export default function AdminFooterPaymentMethodsPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminFooterPaymentMethod[]>([]);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminFooterPaymentMethods()
      .then(setItems)
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount, loading flag starts true and is only ever cleared from the promise callback
    load();
  }, []);

  async function handleCreate(payload: FooterPaymentMethodPayload) {
    await createAdminFooterPaymentMethod(payload);
    setAdding(false);
    load();
  }

  async function handleUpdate(id: string, payload: FooterPaymentMethodPayload) {
    await updateAdminFooterPaymentMethod(id, payload);
    setEditingId(null);
    load();
  }

  async function handleDelete(id: string) {
    if (!window.confirm(t("confirmDeleteGeneric"))) return;
    await deleteAdminFooterPaymentMethod(id);
    load();
  }

  return (
    <div>
      <Link href="/adminportal/footer" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("backToFooterSettings")}
      </Link>

      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("footerPaymentMethodsTitle")}</h1>
        {!adding && (
          <button
            type="button"
            onClick={() => setAdding(true)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("newPaymentMethod")}
          </button>
        )}
      </div>

      {adding && (
        <div className="mb-6 rounded-2xl bg-white p-6 shadow-soft">
          <FooterPaymentMethodForm onSubmit={handleCreate} onCancel={() => setAdding(false)} submitLabel={t("save")} />
        </div>
      )}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 && !adding ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="space-y-4">
          {items.map((item) =>
            editingId === item.id ? (
              <div key={item.id} className="rounded-2xl bg-white p-6 shadow-soft">
                <FooterPaymentMethodForm
                  initial={item}
                  onSubmit={(payload) => handleUpdate(item.id, payload)}
                  onCancel={() => setEditingId(null)}
                  submitLabel={t("save")}
                />
              </div>
            ) : (
              <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-soft">
                <div>
                  <div className="flex items-center gap-2">
                    <p className="font-semibold text-ink">{item.name[locale]}</p>
                    <span
                      className={`rounded-full px-2 py-0.5 text-caption font-medium ${
                        item.is_active ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {item.is_active ? t("active") : t("inactive")}
                    </span>
                  </div>
                  <p className="text-small text-ink/60">
                    {item.icon} · {t("sortOrder")}: {item.sort_order}
                  </p>
                </div>
                <div className="flex gap-3 text-small">
                  <button type="button" onClick={() => setEditingId(item.id)} className="text-primary hover:underline">
                    {t("edit")}
                  </button>
                  <button type="button" onClick={() => handleDelete(item.id)} className="text-error hover:underline">
                    {t("delete")}
                  </button>
                </div>
              </div>
            ),
          )}
        </div>
      )}
    </div>
  );
}

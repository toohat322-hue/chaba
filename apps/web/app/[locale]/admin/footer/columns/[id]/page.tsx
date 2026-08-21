"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import {
  createAdminFooterLink,
  deleteAdminFooterLink,
  getAdminFooterColumns,
  updateAdminFooterLink,
  type AdminFooterColumn,
  type FooterLinkPayload,
} from "@/lib/api";
import { FooterLinkForm } from "@/components/admin/FooterLinkForm";

type Locale = "ar" | "fr" | "en";

export default function AdminFooterColumnLinksPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const params = useParams<{ id: string }>();
  const columnId = params.id;

  const [column, setColumn] = useState<AdminFooterColumn | null>(null);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminFooterColumns()
      .then((all) => setColumn(all.find((item) => item.id === columnId) ?? null))
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/column change, loading flag starts true and is only ever cleared from the promise callback
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [columnId]);

  async function handleCreate(payload: FooterLinkPayload) {
    await createAdminFooterLink(columnId, payload);
    setAdding(false);
    load();
  }

  async function handleUpdate(id: string, payload: FooterLinkPayload) {
    await updateAdminFooterLink(id, payload);
    setEditingId(null);
    load();
  }

  async function handleDelete(id: string) {
    if (!window.confirm(t("confirmDeleteGeneric"))) return;
    await deleteAdminFooterLink(id);
    load();
  }

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (!column) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  return (
    <div>
      <Link href="/admin/footer/columns" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("footerColumnsTitle")}
      </Link>

      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{column.title[locale]}</h1>
        {!adding && (
          <button
            type="button"
            onClick={() => setAdding(true)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("newLink")}
          </button>
        )}
      </div>

      {adding && (
        <div className="mb-6 rounded-2xl bg-white p-6 shadow-soft">
          <FooterLinkForm onSubmit={handleCreate} onCancel={() => setAdding(false)} submitLabel={t("save")} />
        </div>
      )}

      {column.links.length === 0 && !adding ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="space-y-4">
          {column.links.map((link) =>
            editingId === link.id ? (
              <div key={link.id} className="rounded-2xl bg-white p-6 shadow-soft">
                <FooterLinkForm
                  initial={link}
                  onSubmit={(payload) => handleUpdate(link.id, payload)}
                  onCancel={() => setEditingId(null)}
                  submitLabel={t("save")}
                />
              </div>
            ) : (
              <div key={link.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-soft">
                <div>
                  <div className="flex items-center gap-2">
                    <p className="font-semibold text-ink">{link.label[locale]}</p>
                    <span
                      className={`rounded-full px-2 py-0.5 text-caption font-medium ${
                        link.is_active ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {link.is_active ? t("active") : t("inactive")}
                    </span>
                  </div>
                  <p dir="ltr" className="text-end text-small text-ink/60">
                    {link.url}
                  </p>
                </div>
                <div className="flex gap-3 text-small">
                  <button type="button" onClick={() => setEditingId(link.id)} className="text-primary hover:underline">
                    {t("edit")}
                  </button>
                  <button type="button" onClick={() => handleDelete(link.id)} className="text-error hover:underline">
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

"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getAdminNewsletterSubscribers, type AdminNewsletterSubscriber } from "@/lib/api";

export default function AdminFooterSubscribersPage() {
  const t = useTranslations("Admin");

  const [items, setItems] = useState<AdminNewsletterSubscriber[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    setLoading(true);
    getAdminNewsletterSubscribers({ page })
      .then((res) => {
        setItems(res.items);
        setLastPage(res.meta.last_page);
      })
      .finally(() => setLoading(false));
  }, [page]);

  return (
    <div>
      <Link href="/adminportal/footer" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("backToFooterSettings")}
      </Link>

      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("footerSubscribersTitle")}</h1>

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <>
          <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
            <table className="w-full text-start text-small">
              <thead>
                <tr className="border-b border-primary/10 text-ink/60">
                  <th className="px-4 py-3 text-start font-medium">{t("email")}</th>
                  <th className="px-4 py-3 text-start font-medium">{t("locale")}</th>
                  <th className="px-4 py-3 text-start font-medium">{t("subscribedAt")}</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id} className="border-b border-primary/5 last:border-0">
                    <td dir="ltr" className="px-4 py-3 text-end">
                      {item.email}
                    </td>
                    <td className="px-4 py-3 uppercase">{item.locale ?? "—"}</td>
                    <td className="px-4 py-3">{new Date(item.subscribed_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {lastPage > 1 && (
            <div className="mt-4 flex items-center justify-center gap-3 text-small">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="rounded-full border border-primary/15 px-4 py-1.5 disabled:opacity-40"
              >
                ←
              </button>
              <span className="text-ink/60">
                {page} / {lastPage}
              </span>
              <button
                type="button"
                disabled={page >= lastPage}
                onClick={() => setPage((p) => p + 1)}
                className="rounded-full border border-primary/15 px-4 py-1.5 disabled:opacity-40"
              >
                →
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
}

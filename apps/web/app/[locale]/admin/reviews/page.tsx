"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getAdminReviews, updateAdminReviewStatus, type Review, type ProductListMeta, type LocalizedText } from "@/lib/api";
import { StarRating } from "@/components/reviews/StarRating";

type Locale = "ar" | "fr" | "en";
type AdminReview = Review & { product: { id: string; slug: string; name: LocalizedText } };

export default function AdminReviewsPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminReview[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState("pending");
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  async function load(statusFilter: string, pageNum: number) {
    setLoading(true);
    try {
      const res = await getAdminReviews({ status: statusFilter || undefined, page: pageNum });
      setItems(res.items);
      setMeta(res.meta);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(status, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleFilter(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(status, 1);
  }

  async function handleModerate(id: string, newStatus: "approved" | "rejected") {
    setError(null);
    try {
      await updateAdminReviewStatus(id, newStatus);
      load(status, page);
    } catch {
      setError(t("errorGeneric"));
    }
  }

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("reviewsTitle")}</h1>

      <form onSubmit={handleFilter} className="mb-4 flex flex-wrap gap-3">
        <select
          value={status}
          onChange={(event) => setStatus(event.target.value)}
          className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
          <option value="">{t("allStatuses")}</option>
          <option value="pending">{t("reviewPending")}</option>
          <option value="approved">{t("reviewApproved")}</option>
          <option value="rejected">{t("reviewRejected")}</option>
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
        <div className="space-y-3">
          {items.map((review) => (
            <div key={review.id} className="rounded-2xl bg-white p-5 shadow-soft">
              <div className="mb-2 flex items-center justify-between">
                <div>
                  <p className="text-small font-medium text-ink">{review.product.name[locale]}</p>
                  <p className="text-caption text-ink/50">{review.reviewer_name}</p>
                </div>
                <StarRating value={review.rating} />
              </div>

              {review.title && <p className="font-medium text-ink">{review.title}</p>}
              {review.body && <p className="text-small text-ink/70">{review.body}</p>}

              <div className="mt-3 flex items-center gap-3 text-caption text-ink/50">
                {review.is_verified_purchase && (
                  <span className="rounded-full bg-success/10 px-2 py-0.5 text-success">{t("reviewVerified")}</span>
                )}
                <span>{t("reviewReportCount", { count: review.report_count ?? 0 })}</span>
              </div>

              {review.status === "pending" && (
                <div className="mt-3 flex gap-3">
                  <button
                    type="button"
                    onClick={() => handleModerate(review.id, "approved")}
                    className="rounded-full bg-primary px-4 py-1.5 text-caption font-semibold text-background hover:bg-primary/90"
                  >
                    {t("reviewApprove")}
                  </button>
                  <button
                    type="button"
                    onClick={() => handleModerate(review.id, "rejected")}
                    className="rounded-full bg-error/10 px-4 py-1.5 text-caption font-semibold text-error hover:bg-error/20"
                  >
                    {t("reviewReject")}
                  </button>
                </div>
              )}
            </div>
          ))}
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
          <span className="text-ink/60">{page} / {meta.last_page}</span>
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

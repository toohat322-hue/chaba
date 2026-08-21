"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import {
  ApiError,
  deleteReview,
  getMyReview,
  getProductReviews,
  reportReview,
  submitReview,
  uploadReviewImage,
  type ProductListMeta,
  type Review,
} from "@/lib/api";
import { StarRating } from "./StarRating";
import { StarRatingInput } from "./StarRatingInput";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export function ReviewsSection({ productSlug }: { productSlug: string }) {
  const t = useTranslations("Review");
  const { customer, loading: authLoading } = useCustomerAuth();

  const [reviews, setReviews] = useState<Review[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const [myReview, setMyReview] = useState<Review | null>(null);
  const [myReviewLoading, setMyReviewLoading] = useState(true);
  const [editing, setEditing] = useState(false);

  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  function loadList(pageNum: number) {
    setLoading(true);
    getProductReviews(productSlug, pageNum)
      .then((res) => {
        setReviews(res.items);
        setMeta(res.meta);
      })
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    loadList(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- no review to fetch when logged out, loading flag just needs to clear
      setMyReviewLoading(false);
      return;
    }

    getMyReview(productSlug)
      .then((review) => {
        setMyReview(review);
        if (review) {
          setRating(review.rating);
          setTitle(review.title ?? "");
          setBody(review.body ?? "");
        }
      })
      .finally(() => setMyReviewLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    setSubmitting(true);

    try {
      const review = await submitReview(productSlug, {
        rating,
        title: title || undefined,
        body: body || undefined,
      });
      setMyReview(review);
      setEditing(false);
      loadList(1);
      setPage(1);
    } catch (err) {
      setFormError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDelete() {
    if (!myReview) return;

    await deleteReview(myReview.id);
    setMyReview(null);
    setRating(0);
    setTitle("");
    setBody("");
    loadList(1);
    setPage(1);
  }

  async function handleUploadImage(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file || !myReview) return;

    const image = await uploadReviewImage(myReview.id, file);
    setMyReview({ ...myReview, images: [...myReview.images, image] });
    event.target.value = "";
  }

  async function handleReport(reviewId: string) {
    const reason = window.prompt(t("reportReasonPrompt"));
    if (!reason) return;

    await reportReview(reviewId, reason);
    window.alert(t("reportThanks"));
  }

  return (
    <section className="mx-auto max-w-3xl px-6 pb-16">
      <h2 className="mb-6 text-h2 font-semibold text-primary">{t("title")}</h2>

      <div className="mb-8 rounded-2xl bg-white p-6 shadow-soft">
        {authLoading || myReviewLoading ? null : !customer ? (
          <p className="text-small text-ink/60">
            {t("loginToReview")}{" "}
            <Link href="/login" className="font-medium text-primary hover:underline">
              {t("loginCta")}
            </Link>
          </p>
        ) : myReview && !editing ? (
          <div>
            <div className="mb-2 flex items-center gap-2">
              <StarRating value={myReview.rating} />
              {myReview.is_verified_purchase && (
                <span className="rounded-full bg-success/10 px-2 py-0.5 text-caption text-success">
                  {t("verifiedBadge")}
                </span>
              )}
              {myReview.status === "pending" && (
                <span className="rounded-full bg-warning/10 px-2 py-0.5 text-caption text-warning">
                  {t("pendingBadge")}
                </span>
              )}
            </div>
            {myReview.title && <p className="font-medium text-ink">{myReview.title}</p>}
            {myReview.body && <p className="text-small text-ink/70">{myReview.body}</p>}

            <div className="mt-3 flex flex-wrap gap-2">
              {myReview.images.map((image) => (
                // eslint-disable-next-line @next/next/no-img-element
                <img key={image.id} src={image.url} alt={t("reviewPhotoAlt")} className="h-16 w-16 rounded-lg object-cover" />
              ))}
              {myReview.images.length < 5 && (
                <label className="flex h-16 w-16 cursor-pointer items-center justify-center rounded-lg border border-dashed border-primary/30 text-caption text-ink/40">
                  +
                  <input type="file" accept="image/*" onChange={handleUploadImage} className="hidden" />
                </label>
              )}
            </div>

            <div className="mt-3 flex gap-3">
              <button type="button" onClick={() => setEditing(true)} className="text-small text-primary hover:underline">
                {t("edit")}
              </button>
              <button type="button" onClick={handleDelete} className="text-small text-error hover:underline">
                {t("delete")}
              </button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-3">
            <StarRatingInput value={rating} onChange={setRating} />
            <input
              type="text"
              placeholder={t("titlePlaceholder")}
              value={title}
              onChange={(event) => setTitle(event.target.value)}
              className={inputClass}
            />
            <textarea
              placeholder={t("bodyPlaceholder")}
              value={body}
              onChange={(event) => setBody(event.target.value)}
              rows={3}
              className={inputClass}
            />
            {formError && <p className="text-small text-error">{formError}</p>}
            <div className="flex gap-3">
              <button
                type="submit"
                disabled={submitting || rating === 0}
                className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
              >
                {t("submit")}
              </button>
              {myReview && (
                <button type="button" onClick={() => setEditing(false)} className="text-small text-ink/60 hover:text-ink">
                  {t("cancel")}
                </button>
              )}
            </div>
          </form>
        )}
      </div>

      {loading ? null : reviews.length === 0 ? (
        <p className="text-small text-ink/50">{t("noReviews")}</p>
      ) : (
        <div className="space-y-4">
          {reviews.map((review) => (
            <div key={review.id} className="rounded-2xl bg-white p-5 shadow-soft">
              <div className="mb-1 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <StarRating value={review.rating} />
                  {review.is_verified_purchase && (
                    <span className="rounded-full bg-success/10 px-2 py-0.5 text-caption text-success">
                      {t("verifiedBadge")}
                    </span>
                  )}
                </div>
                <span className="text-caption text-ink/40">{new Date(review.created_at).toLocaleDateString()}</span>
              </div>
              <p className="mb-1 text-small font-medium text-ink">{review.reviewer_name}</p>
              {review.title && <p className="font-medium text-ink">{review.title}</p>}
              {review.body && <p className="text-small text-ink/70">{review.body}</p>}
              {review.images.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                  {review.images.map((image) => (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img key={image.id} src={image.url} alt={t("reviewPhotoAlt")} className="h-16 w-16 rounded-lg object-cover" />
                  ))}
                </div>
              )}
              {customer && review.id !== myReview?.id && (
                <button
                  type="button"
                  onClick={() => handleReport(review.id)}
                  className="mt-2 text-caption text-ink/40 hover:text-error"
                >
                  {t("report")}
                </button>
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
    </section>
  );
}

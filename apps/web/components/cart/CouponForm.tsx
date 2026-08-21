"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { ApiError } from "@/lib/api";
import { useCart } from "@/components/cart/CartProvider";

function errorMessage(error: unknown, t: (key: string) => string): string {
  if (error instanceof ApiError) {
    const known = [
      "coupon_not_found",
      "coupon_inactive",
      "coupon_not_started",
      "coupon_expired",
      "coupon_min_order_not_met",
      "coupon_usage_limit_reached",
      "coupon_customer_limit_reached",
      "coupon_type_not_supported",
    ];
    if (known.includes(error.code)) {
      const camel = error.code.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase());
      return t(camel);
    }
  }
  return t("couponGenericError");
}

export function CouponForm({ coupon }: { coupon: { code: string; type: string } | null }) {
  const t = useTranslations("Cart");
  const { applyCoupon, removeCoupon } = useCart();
  const [code, setCode] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!code.trim()) return;

    setError(null);
    setSubmitting(true);
    try {
      await applyCoupon(code.trim());
      setCode("");
    } catch (err) {
      setError(errorMessage(err, t));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleRemove() {
    setError(null);
    await removeCoupon();
  }

  if (coupon) {
    return (
      <div className="flex items-center justify-between rounded-xl bg-success/10 px-4 py-3 text-small text-ink">
        <span>
          {t("couponApplied")}: <strong>{coupon.code}</strong>
        </span>
        <button
          type="button"
          onClick={handleRemove}
          className="rounded text-small font-medium text-ink/60 transition-colors hover:text-error focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
        >
          {t("removeCoupon")}
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-wrap gap-2">
      <input
        type="text"
        value={code}
        onChange={(event) => setCode(event.target.value)}
        placeholder={t("couponCode")}
        className="min-w-0 flex-1 rounded-lg border border-primary/15 bg-white px-4 py-3 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
      />
      <button
        type="submit"
        disabled={submitting}
        className="rounded-lg bg-primary px-5 py-3 text-body font-semibold text-background transition-colors hover:bg-primary/90 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
      >
        {t("applyCoupon")}
      </button>
      {error && <p className="w-full text-small text-error" role="alert">{error}</p>}
    </form>
  );
}

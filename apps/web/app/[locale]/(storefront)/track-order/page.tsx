"use client";

import { Suspense, useEffect, useRef, useState, type FormEvent } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { ApiError, trackOrder, type Order } from "@/lib/api";
import { OrderSummaryCard } from "@/components/checkout/OrderSummaryCard";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function TrackOrderForm() {
  const t = useTranslations("Checkout");
  const searchParams = useSearchParams();

  // Prefilled from a link that already carries both (e.g. the order
  // confirmation email's "Track your order" button) — the customer
  // shouldn't have to retype what the link already told us.
  const [orderNumber, setOrderNumber] = useState(searchParams.get("order_number") ?? "");
  const [phone, setPhone] = useState(searchParams.get("phone") ?? "");
  const [order, setOrder] = useState<Order | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const autoSubmitted = useRef(false);

  async function track(number: string, phoneValue: string) {
    setError(null);
    setOrder(null);
    setLoading(true);

    try {
      const result = await trackOrder(number, phoneValue);
      setOrder(result);
    } catch (err) {
      setError(err instanceof ApiError ? t("trackNotFound") : t("errorGeneric"));
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (autoSubmitted.current || !orderNumber || !phone) return;
    autoSubmitted.current = true;
    track(orderNumber, phone);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- one-time auto-submit from the link's query params, not re-run on later manual edits
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    track(orderNumber, phone);
  }

  return (
    <div className="mx-auto max-w-xl px-6 py-16">
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("trackTitle")}</h1>

      <form onSubmit={handleSubmit} className="mb-8 space-y-4">
        <input
          type="text"
          required
          placeholder={t("trackOrderNumber")}
          value={orderNumber}
          onChange={(event) => setOrderNumber(event.target.value)}
          className={inputClass}
        />
        <input
          type="tel"
          required
          placeholder={t("trackPhone")}
          value={phone}
          onChange={(event) => setPhone(event.target.value)}
          className={inputClass}
        />

        {error && <p className="text-small text-error">{error}</p>}

        <button
          type="submit"
          disabled={loading}
          className="w-full rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("trackSubmit")}
        </button>
      </form>

      {order && <OrderSummaryCard order={order} />}
    </div>
  );
}

export default function TrackOrderPage() {
  return (
    <Suspense fallback={null}>
      <TrackOrderForm />
    </Suspense>
  );
}

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { OrderSummaryCard } from "@/components/checkout/OrderSummaryCard";
import type { Order } from "@/lib/api";

export default function OrderConfirmationPage() {
  const t = useTranslations("Checkout");
  const params = useParams<{ orderNumber: string }>();
  const orderNumber = params.orderNumber;

  const [order, setOrder] = useState<Order | null>(null);
  const [checked, setChecked] = useState(false);

  useEffect(() => {
    const stored = window.sessionStorage.getItem("chaba_last_order");
    if (stored) {
      const parsed: Order = JSON.parse(stored);
      if (parsed.order_number === orderNumber) {
        // eslint-disable-next-line react-hooks/set-state-in-effect -- client-only handoff from checkout via sessionStorage, must run after mount
        setOrder(parsed);
      }
    }
    setChecked(true);
  }, [orderNumber]);

  if (!checked) {
    return null;
  }

  if (!order) {
    return (
      <div className="mx-auto flex max-w-xl flex-col items-center gap-4 px-6 py-24 text-center">
        <h1 className="text-h2 font-semibold text-primary">{orderNumber}</h1>
        <p className="text-body text-ink/60">{t("trackTitle")}</p>
        <Link
          href="/track-order"
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90"
        >
          {t("trackThisOrder")}
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-xl px-6 py-16">
      <div className="mb-8 text-center">
        <h1 className="mb-2 text-h1 font-semibold text-primary">{t("confirmationTitle")}</h1>
        <p className="text-body text-ink/60">{t("confirmationThanks")}</p>
      </div>

      <OrderSummaryCard order={order} />

      <div className="mt-8 text-center">
        <Link href="/" className="text-small text-ink/60 hover:text-ink">
          {t("backToHome")}
        </Link>
      </div>
    </div>
  );
}

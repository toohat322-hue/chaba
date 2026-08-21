"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link, usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { getMyOrder, type Order } from "@/lib/api";
import { OrderSummaryCard } from "@/components/checkout/OrderSummaryCard";
import { ShabaLoader } from "@/components/ui/ShabaLoader";

export default function MyOrderDetailPage() {
  const t = useTranslations("Admin");
  const tLoading = useTranslations("Loading");
  const params = useParams<{ orderNumber: string }>();
  const orderNumber = params.orderNumber;
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    getMyOrder(orderNumber)
      .then(setOrder)
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer, orderNumber]);

  if (authLoading || loading) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  if (!customer) {
    return null;
  }

  if (!order) {
    return (
      <div className="mx-auto max-w-xl px-6 py-16 text-center">
        <p className="text-body text-ink/60">{t("noResults")}</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-xl px-6 py-10">
      <Link href="/orders" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("back")}
      </Link>

      <OrderSummaryCard order={order} />
    </div>
  );
}

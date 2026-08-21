"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link, usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { AccountNav } from "@/components/customer/AccountNav";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import { getMyOrders, type Order } from "@/lib/api";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

export default function MyOrdersPage() {
  const t = useTranslations("Auth");
  const tAdmin = useTranslations("Admin");
  const tStatus = useTranslations("OrderStatus");
  const tLoading = useTranslations("Loading");
  const locale = useLocale() as Locale;
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    getMyOrders()
      .then((res) => setOrders(res.items))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer]);

  if (authLoading || loading) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  if (!customer) {
    return null;
  }

  return (
    <div className="mx-auto max-w-3xl px-6 py-10">
      <AccountNav />
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("myOrders")}</h1>

      {orders.length === 0 ? (
        <p className="text-body text-ink/60">{tAdmin("noResults")}</p>
      ) : (
        <div className="space-y-3">
          {orders.map((order) => (
            <Link
              key={order.id}
              href={`/orders/${order.order_number}`}
              className="flex items-center justify-between rounded-2xl bg-white p-5 shadow-soft transition-shadow hover:shadow-md"
            >
              <div>
                <p className="font-semibold text-ink">{order.order_number}</p>
                <p className="text-small text-ink/60">{new Date(order.created_at).toLocaleDateString(locale)}</p>
              </div>
              <div className="text-end">
                <p className="text-small font-medium text-primary">{tStatus(order.order_status)}</p>
                <p className="text-small text-ink/70">{formatPrice(order.grand_total, locale)}</p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

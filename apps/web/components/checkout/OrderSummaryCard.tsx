"use client";

import { useLocale, useTranslations } from "next-intl";
import { formatPrice } from "@/lib/currency";
import { CartTotals } from "@/components/cart/CartTotals";
import type { Order } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export function OrderSummaryCard({ order }: { order: Order }) {
  const locale = useLocale() as Locale;
  const t = useTranslations("Checkout");
  const tStatus = useTranslations("OrderStatus");
  const tAdmin = useTranslations("Admin");

  const shipment = order.shipments?.[order.shipments.length - 1] ?? null;

  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <div className="mb-4 flex items-center justify-between">
        <div>
          <p className="text-small text-ink/60">{t("orderNumberLabel")}</p>
          <p className="text-h3 font-semibold text-primary">{order.order_number}</p>
        </div>
        <span className="rounded-full bg-primary/10 px-3 py-1 text-small font-medium text-primary">
          {tStatus(order.order_status)}
        </span>
      </div>

      {order.address && (
        <div className="mb-4 border-t border-primary/10 pt-4 text-small text-ink/70">
          <p>
            {order.address.full_name} · {order.address.phone}
          </p>
          <p>
            {order.address.wilaya.name?.[locale]}
            {order.address.commune ? `, ${order.address.commune[locale]}` : ""}
          </p>
          <p>{order.address.address_line}</p>
        </div>
      )}

      {order.items && order.items.length > 0 && (
        <ul className="mb-4 space-y-2 border-t border-primary/10 pt-4 text-small text-ink/70">
          {order.items.map((item) => (
            <li key={item.id} className="flex justify-between gap-3">
              <span>
                {item.product_name}
                {item.size && <span className="text-ink/50"> — {item.size}</span>} × {item.quantity}
              </span>
              <span>{formatPrice(item.line_total, locale)}</span>
            </li>
          ))}
        </ul>
      )}

      <div className="border-t border-primary/10 pt-4">
        <CartTotals
          subtotal={order.subtotal}
          deliveryFee={order.delivery_fee}
          total={order.grand_total}
          locale={locale}
          size="compact"
        />
      </div>

      {shipment && (
        <div className="mt-4 border-t border-primary/10 pt-4 text-small text-ink/70">
          <p className="mb-2 font-medium text-ink/70">{tAdmin("shipmentTitle")}</p>
          {shipment.courier_partner && (
            <p>
              {tAdmin("courier")}: {shipment.courier_partner}
            </p>
          )}
          {shipment.tracking_number && (
            <p>
              {tAdmin("trackingNumber")}: {shipment.tracking_number}
            </p>
          )}
          <p>
            {tAdmin("status")}: {tAdmin(`shipment_${shipment.status}`)}
          </p>
          {shipment.estimated_delivery_date && (
            <p>
              {tAdmin("estimatedDelivery")}: {shipment.estimated_delivery_date}
            </p>
          )}
        </div>
      )}

      {order.status_history && order.status_history.length > 0 && (
        <div className="mt-4 border-t border-primary/10 pt-4">
          <p className="mb-2 text-small font-medium text-ink/70">{t("statusHistory")}</p>
          <ul className="space-y-1 text-caption text-ink/50">
            {order.status_history.map((entry, index) => (
              <li key={index}>
                {tStatus(entry.status)} — {new Date(entry.created_at).toLocaleString(locale)}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}

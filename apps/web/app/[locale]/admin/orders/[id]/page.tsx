"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import {
  ApiError,
  createAdminShipment,
  getAdminOrder,
  updateAdminOrderStatus,
  updateAdminShipmentStatus,
  type Order,
  type OrderStatus,
  type ShipmentStatus,
} from "@/lib/api";
import { OrderSummaryCard } from "@/components/checkout/OrderSummaryCard";

const STATUSES: OrderStatus[] = [
  "pending", "confirmed", "processing", "ready_to_ship", "shipped",
  "out_for_delivery", "delivered", "cancelled", "failed", "returned",
  "refunded", "partially_refunded",
];

const SHIPMENT_STATUSES: ShipmentStatus[] = [
  "picked_up", "in_transit", "out_for_delivery", "delivered", "failed", "returned",
];

export default function AdminOrderDetailPage() {
  const t = useTranslations("Admin");
  const tStatus = useTranslations("OrderStatus");
  const params = useParams<{ id: string }>();
  const orderId = params.id;

  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [newStatus, setNewStatus] = useState<OrderStatus | "">("");
  const [note, setNote] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [courierPartner, setCourierPartner] = useState("");
  const [trackingNumber, setTrackingNumber] = useState("");
  const [estimatedDate, setEstimatedDate] = useState("");
  const [shipmentStatus, setShipmentStatus] = useState<ShipmentStatus | "">("");
  const [failureReason, setFailureReason] = useState("");
  const [shipmentSaving, setShipmentSaving] = useState(false);
  const [shipmentError, setShipmentError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminOrder(orderId)
      .then(setOrder)
      .finally(() => setLoading(false));
  }

  // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/orderId change, loading flag starts true and is only ever cleared from the promise callback
  useEffect(load, [orderId]);

  async function handleUpdateStatus(event: FormEvent) {
    event.preventDefault();
    if (!newStatus) return;

    setError(null);
    setSaving(true);
    try {
      const updated = await updateAdminOrderStatus(orderId, newStatus, note || undefined);
      setOrder(updated);
      setNewStatus("");
      setNote("");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setSaving(false);
    }
  }

  const shipment = order?.shipments?.[order.shipments.length - 1] ?? null;

  function resetShipmentForm() {
    setCourierPartner("");
    setTrackingNumber("");
    setEstimatedDate("");
    setShipmentStatus("");
    setFailureReason("");
  }

  async function handleCreateShipment(event: FormEvent) {
    event.preventDefault();
    if (!order) return;

    setShipmentError(null);
    setShipmentSaving(true);
    try {
      const created = await createAdminShipment(order.id, {
        courier_partner: courierPartner || undefined,
        tracking_number: trackingNumber || undefined,
        estimated_delivery_date: estimatedDate || undefined,
      });
      setOrder({ ...order, shipments: [...(order.shipments ?? []), created] });
      resetShipmentForm();
    } catch (err) {
      setShipmentError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setShipmentSaving(false);
    }
  }

  async function handleUpdateShipmentStatus(event: FormEvent) {
    event.preventDefault();
    if (!order || !shipment || !shipmentStatus) return;

    setShipmentError(null);
    setShipmentSaving(true);
    try {
      const updated = await updateAdminShipmentStatus(shipment.id, {
        status: shipmentStatus as Exclude<ShipmentStatus, "pending">,
        courier_partner: courierPartner || undefined,
        tracking_number: trackingNumber || undefined,
        estimated_delivery_date: estimatedDate || undefined,
        failure_reason: shipmentStatus === "failed" ? failureReason || undefined : undefined,
      });
      setOrder({
        ...order,
        shipments: (order.shipments ?? []).map((s) => (s.id === updated.id ? updated : s)),
      });
      resetShipmentForm();
    } catch (err) {
      setShipmentError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setShipmentSaving(false);
    }
  }

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (!order) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  return (
    <div className="max-w-2xl">
      <Link href="/admin/orders" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("back")}
      </Link>

      {order.payment_method === "whatsapp" && (
        <div className="mb-6 flex items-center justify-between rounded-2xl bg-white p-4 shadow-soft">
          <span className="text-small text-ink/70">
            {t("whatsappOrder")} —{" "}
            {order.whatsapp_status === "opened" ? t("whatsappStatusOpened") : t("whatsappStatusPending")}
          </span>
          <a
            href={`https://wa.me/${order.customer.phone.replace(/\D/g, "")}`}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-full bg-[#20B858] px-4 py-2 text-small font-semibold text-white hover:bg-[#1ea34e]"
          >
            {t("openWhatsApp")}
          </a>
        </div>
      )}

      <OrderSummaryCard order={order} />

      <div className="mt-6 rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="mb-4 text-h3 font-semibold text-ink">{t("newStatus")}</h2>

        <form onSubmit={handleUpdateStatus} className="flex flex-wrap items-end gap-3">
          <select
            required
            value={newStatus}
            onChange={(event) => setNewStatus(event.target.value as OrderStatus)}
            className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            <option value="" disabled>
              {t("newStatus")}
            </option>
            {STATUSES.map((status) => (
              <option key={status} value={status}>
                {tStatus(status)}
              </option>
            ))}
          </select>

          <input
            type="text"
            placeholder={t("noteOptional")}
            value={note}
            onChange={(event) => setNote(event.target.value)}
            className="min-w-[14rem] flex-1 rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />

          <button
            type="submit"
            disabled={saving}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {t("apply")}
          </button>
        </form>

        {error && <p className="mt-3 text-small text-error">{error}</p>}
      </div>

      <div className="mt-6 rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="mb-4 text-h3 font-semibold text-ink">{t("shipmentTitle")}</h2>

        {shipment ? (
          <div className="mb-4 space-y-1 text-small text-ink/70">
            <p>
              <span className="font-medium text-ink">{t("courier")}:</span> {shipment.courier_partner ?? "—"}
            </p>
            <p>
              <span className="font-medium text-ink">{t("trackingNumber")}:</span> {shipment.tracking_number ?? "—"}
            </p>
            <p>
              <span className="font-medium text-ink">{t("status")}:</span> {t(`shipment_${shipment.status}`)}
            </p>
            {shipment.estimated_delivery_date && (
              <p>
                <span className="font-medium text-ink">{t("estimatedDelivery")}:</span>{" "}
                {shipment.estimated_delivery_date}
              </p>
            )}
            {shipment.failure_reason && (
              <p className="text-error">
                <span className="font-medium">{t("failureReason")}:</span> {shipment.failure_reason}
              </p>
            )}
          </div>
        ) : (
          <p className="mb-4 text-small text-ink/50">{t("noShipmentYet")}</p>
        )}

        <form onSubmit={shipment ? handleUpdateShipmentStatus : handleCreateShipment} className="space-y-3">
          <div className="flex flex-wrap gap-3">
            <input
              type="text"
              placeholder={t("courier")}
              value={courierPartner}
              onChange={(event) => setCourierPartner(event.target.value)}
              className="min-w-[10rem] flex-1 rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
            <input
              type="text"
              placeholder={t("trackingNumber")}
              value={trackingNumber}
              onChange={(event) => setTrackingNumber(event.target.value)}
              className="min-w-[10rem] flex-1 rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
            <input
              type="date"
              value={estimatedDate}
              onChange={(event) => setEstimatedDate(event.target.value)}
              className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>

          {shipment && (
            <div className="flex flex-wrap items-end gap-3">
              <select
                required
                value={shipmentStatus}
                onChange={(event) => setShipmentStatus(event.target.value as ShipmentStatus)}
                className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="" disabled>
                  {t("newShipmentStatus")}
                </option>
                {SHIPMENT_STATUSES.map((status) => (
                  <option key={status} value={status}>
                    {t(`shipment_${status}`)}
                  </option>
                ))}
              </select>
              {shipmentStatus === "failed" && (
                <input
                  type="text"
                  placeholder={t("failureReason")}
                  value={failureReason}
                  onChange={(event) => setFailureReason(event.target.value)}
                  className="min-w-[10rem] flex-1 rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
                />
              )}
            </div>
          )}

          <button
            type="submit"
            disabled={shipmentSaving || (Boolean(shipment) && !shipmentStatus)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {shipment ? t("apply") : t("createShipment")}
          </button>

          {shipmentError && <p className="text-small text-error">{shipmentError}</p>}
        </form>
      </div>
    </div>
  );
}

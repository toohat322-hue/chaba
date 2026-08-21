"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { Link, usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import {
  deleteNotification,
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  type NotificationItem,
  type ProductListMeta,
} from "@/lib/api";

function describeNotification(item: NotificationItem, t: ReturnType<typeof useTranslations>): string {
  const orderNumber = item.payload?.order_number ?? "";

  switch (item.event_type) {
    case "order_created":
      return t("orderCreated", { orderNumber });
    case "order_shipped":
      return t("orderShipped", { orderNumber });
    case "order_delivered":
      return t("orderDelivered", { orderNumber });
    case "order_cancelled":
      return t("orderCancelled", { orderNumber });
    case "payment_successful":
      return t("paymentSuccessful", { orderNumber });
    case "payment_failed":
      return t("paymentFailed", { orderNumber });
    default:
      return item.event_type;
  }
}

function RemoveIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-4 w-4">
      <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
    </svg>
  );
}

export default function NotificationsPage() {
  const t = useTranslations("Notifications");
  const tAuth = useTranslations("Auth");
  const tLoading = useTranslations("Loading");
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [items, setItems] = useState<NotificationItem[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [unreadCount, setUnreadCount] = useState(0);

  function load(pageNum: number) {
    setLoading(true);
    getNotifications(pageNum)
      .then((res) => {
        setItems(res.items);
        setMeta(res.meta);
        setUnreadCount(res.unread_count);
      })
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer, page]);

  async function handleMarkRead(id: string) {
    const updated = await markNotificationRead(id);
    setItems((current) => current.map((item) => (item.id === id ? updated : item)));
    setUnreadCount((count) => Math.max(0, count - 1));
  }

  async function handleMarkAllRead() {
    await markAllNotificationsRead();
    const now = new Date().toISOString();
    setItems((current) => current.map((item) => ({ ...item, read_at: item.read_at ?? now })));
    setUnreadCount(0);
  }

  async function handleDelete(id: string) {
    await deleteNotification(id);
    setItems((current) => current.filter((item) => item.id !== id));
  }

  if (authLoading || (loading && items.length === 0)) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  if (!customer) {
    return null;
  }

  return (
    <div className="mx-auto max-w-2xl px-6 py-10">
      <div className="mb-8 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("title")}</h1>
        {unreadCount > 0 && (
          <button
            type="button"
            onClick={handleMarkAllRead}
            className="text-small font-medium text-primary hover:underline"
          >
            {t("markAllRead")}
          </button>
        )}
      </div>

      {items.length === 0 ? (
        <p className="py-16 text-center text-body text-ink/50">{t("empty")}</p>
      ) : (
        <div className="overflow-hidden rounded-2xl bg-white shadow-soft">
          {items.map((item) => {
            const label = describeNotification(item, t);
            const content = (
              <span className="flex flex-1 items-center gap-2">
                {!item.read_at && <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />}
                <span className={item.read_at ? "text-ink/60" : "font-medium text-ink"}>{label}</span>
              </span>
            );

            return (
              <div
                key={item.id}
                className="flex items-center gap-3 border-b border-primary/5 px-5 py-4 text-small last:border-b-0"
              >
                {item.payload?.order_number ? (
                  <Link
                    href={`/orders/${item.payload.order_number}`}
                    onClick={() => !item.read_at && void handleMarkRead(item.id)}
                    className="flex flex-1 items-center gap-2 hover:text-primary"
                  >
                    {content}
                  </Link>
                ) : (
                  <button
                    type="button"
                    onClick={() => !item.read_at && handleMarkRead(item.id)}
                    className="flex flex-1 items-center gap-2 text-start"
                  >
                    {content}
                  </button>
                )}
                <button
                  type="button"
                  onClick={() => handleDelete(item.id)}
                  aria-label={tAuth("delete")}
                  className="shrink-0 rounded-full p-1.5 text-ink/40 transition-colors hover:bg-error/10 hover:text-error"
                >
                  <RemoveIcon />
                </button>
              </div>
            );
          })}
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
    </div>
  );
}

"use client";

import { useEffect, useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { getNotifications, markAllNotificationsRead, markNotificationRead, type NotificationItem } from "@/lib/api";

function BellIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path d="M6 8a6 6 0 0 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6z" strokeLinecap="round" strokeLinejoin="round" />
      <path d="M10 19a2 2 0 0 0 4 0" strokeLinecap="round" />
    </svg>
  );
}

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

const focusRing =
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background";

export function NotificationBell() {
  const t = useTranslations("Notifications");
  const tNav = useTranslations("Nav");
  const { customer } = useCustomerAuth();

  const [items, setItems] = useState<NotificationItem[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Keyed on customer.id (not a one-shot `loaded` flag) — without this,
    // switching accounts on the same tab without a full reload (log out,
    // log in as someone else) kept showing the previous customer's
    // notifications and unread badge, since the old fetch had already set
    // loaded=true and the effect never ran again.
    if (!customer) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- clearing stale data from the previous identity when it disappears, not deriving state from a render
      setItems([]);
      setUnreadCount(0);
      return;
    }

    let cancelled = false;

    getNotifications(1)
      .then((res) => {
        if (cancelled) return;
        setItems(res.items);
        setUnreadCount(res.unread_count);
      })
      .catch(() => undefined);

    return () => {
      cancelled = true;
    };
  }, [customer]);

  useEffect(() => {
    if (!open) return;

    function handleClick(event: MouseEvent) {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    function handleKey(event: KeyboardEvent) {
      if (event.key === "Escape") setOpen(false);
    }

    document.addEventListener("mousedown", handleClick);
    document.addEventListener("keydown", handleKey);
    return () => {
      document.removeEventListener("mousedown", handleClick);
      document.removeEventListener("keydown", handleKey);
    };
  }, [open]);

  if (!customer) return null;

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

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        aria-label={tNav("notifications")}
        aria-expanded={open}
        className="relative rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
      >
        <BellIcon />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -end-1 flex h-[18px] w-[18px] items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-primary shadow-soft">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute end-0 top-full z-50 mt-2 w-80 rounded-2xl border border-primary/10 bg-white shadow-lift">
          <div className="flex items-center justify-between border-b border-primary/10 px-4 py-3">
            <span className="text-small font-semibold text-ink">{t("title")}</span>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={handleMarkAllRead}
                className={`rounded text-caption font-medium text-primary hover:underline ${focusRing}`}
              >
                {t("markAllRead")}
              </button>
            )}
          </div>

          <div className="max-h-80 overflow-y-auto">
            {items.length === 0 ? (
              <p className="px-4 py-6 text-center text-small text-ink/50">{t("empty")}</p>
            ) : (
              items.slice(0, 8).map((item) => {
                const label = describeNotification(item, t);
                const rowClass = `block w-full border-b border-primary/5 px-4 py-3 text-start text-small transition-colors last:border-b-0 hover:bg-primary/5 ${focusRing} ${
                  item.read_at ? "text-ink/60" : "font-medium text-ink"
                }`;
                const content = (
                  <span className="flex items-center gap-2">
                    {!item.read_at && (
                      <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                    )}
                    {label}
                  </span>
                );

                if (item.payload?.order_number) {
                  return (
                    <Link
                      key={item.id}
                      href={`/orders/${item.payload.order_number}`}
                      onClick={() => {
                        setOpen(false);
                        if (!item.read_at) void handleMarkRead(item.id);
                      }}
                      className={rowClass}
                    >
                      {content}
                    </Link>
                  );
                }

                return (
                  <button
                    type="button"
                    key={item.id}
                    onClick={() => !item.read_at && handleMarkRead(item.id)}
                    className={rowClass}
                  >
                    {content}
                  </button>
                );
              })
            )}
          </div>

          <Link
            href="/notifications"
            onClick={() => setOpen(false)}
            className={`block border-t border-primary/10 px-4 py-3 text-center text-small font-medium text-primary hover:underline ${focusRing}`}
          >
            {t("viewAll")}
          </Link>
        </div>
      )}
    </div>
  );
}

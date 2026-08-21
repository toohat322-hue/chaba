"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import {
  createAdminCoupon,
  deactivateAdminCoupon,
  getAdminCoupons,
  updateAdminCoupon,
  type AdminCoupon,
  type CouponPayload,
} from "@/lib/api";
import { formatPrice } from "@/lib/currency";
import { CouponForm } from "@/components/admin/CouponForm";

type Locale = "ar" | "fr" | "en";

export default function AdminCouponsPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [coupons, setCoupons] = useState<AdminCoupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminCoupons()
      .then((res) => setCoupons(res.items))
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount, loading flag starts true and is only ever cleared from the promise callback
    load();
  }, []);

  async function handleCreate(payload: CouponPayload) {
    await createAdminCoupon(payload);
    setAdding(false);
    load();
  }

  async function handleUpdate(id: string, payload: CouponPayload) {
    await updateAdminCoupon(id, payload);
    setEditingId(null);
    load();
  }

  async function handleDeactivate(id: string) {
    if (!window.confirm(t("confirmDeactivateCoupon"))) return;
    setError(null);
    try {
      await deactivateAdminCoupon(id);
      load();
    } catch {
      // Previously silent — a failed deactivation (permission/conflict
      // error, network blip) looked identical to a successful one, so an
      // admin could believe a coupon was turned off while it stayed live.
      setError(t("errorGeneric"));
    }
  }

  function formatValue(coupon: AdminCoupon): string {
    if (coupon.type === "percentage") return `${coupon.value}%`;
    if (coupon.type === "fixed") return coupon.value != null ? formatPrice(coupon.value, locale) : "—";
    return t("typeFreeShipping");
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("couponsTitle")}</h1>
        {!adding && (
          <button
            type="button"
            onClick={() => setAdding(true)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("newCoupon")}
          </button>
        )}
      </div>

      {error && <p className="mb-4 text-small text-error">{error}</p>}

      {adding && (
        <div className="mb-6 rounded-2xl bg-white p-6 shadow-soft">
          <CouponForm onSubmit={handleCreate} onCancel={() => setAdding(false)} submitLabel={t("save")} />
        </div>
      )}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : coupons.length === 0 && !adding ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="space-y-4">
          {coupons.map((coupon) =>
            editingId === coupon.id ? (
              <div key={coupon.id} className="rounded-2xl bg-white p-6 shadow-soft">
                <CouponForm
                  initial={coupon}
                  onSubmit={(payload) => handleUpdate(coupon.id, payload)}
                  onCancel={() => setEditingId(null)}
                  submitLabel={t("save")}
                />
              </div>
            ) : (
              <div key={coupon.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-soft">
                <div>
                  <div className="flex items-center gap-2">
                    <p className="font-semibold text-ink">{coupon.code}</p>
                    <span
                      className={`rounded-full px-2 py-0.5 text-caption font-medium ${
                        coupon.is_active ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {coupon.is_active ? t("active") : t("inactive")}
                    </span>
                  </div>
                  <p className="text-small text-ink/60">
                    {formatValue(coupon)}
                    {coupon.min_order_value != null && ` · ${t("minOrderValue")}: ${formatPrice(coupon.min_order_value, locale)}`}
                  </p>
                  <p className="text-caption text-ink/50">
                    {t("usageCount")}: {coupon.usage_count}
                    {coupon.usage_limit_total != null ? ` / ${coupon.usage_limit_total}` : ""}
                  </p>
                </div>
                <div className="flex gap-3 text-small">
                  <button type="button" onClick={() => setEditingId(coupon.id)} className="text-primary hover:underline">
                    {t("edit")}
                  </button>
                  {coupon.is_active && (
                    <button type="button" onClick={() => handleDeactivate(coupon.id)} className="text-error hover:underline">
                      {t("deactivate")}
                    </button>
                  )}
                </div>
              </div>
            ),
          )}
        </div>
      )}
    </div>
  );
}

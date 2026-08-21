"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import type { AdminCoupon, CouponPayload } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function toDateInput(value: string | null): string {
  return value ? value.slice(0, 10) : "";
}

export function CouponForm({
  initial,
  onSubmit,
  onCancel,
  submitLabel,
}: {
  initial?: AdminCoupon;
  onSubmit: (payload: CouponPayload) => Promise<void>;
  onCancel?: () => void;
  submitLabel: string;
}) {
  const t = useTranslations("Admin");

  const [code, setCode] = useState(initial?.code ?? "");
  const [type, setType] = useState<CouponPayload["type"]>(initial?.type ?? "percentage");
  const [value, setValue] = useState(initial?.value != null ? String(initial.value) : "");
  const [minOrderValue, setMinOrderValue] = useState(initial?.min_order_value != null ? String(initial.min_order_value) : "");
  const [startDate, setStartDate] = useState(toDateInput(initial?.start_date ?? null));
  const [endDate, setEndDate] = useState(toDateInput(initial?.end_date ?? null));
  const [usageLimitTotal, setUsageLimitTotal] = useState(
    initial?.usage_limit_total != null ? String(initial.usage_limit_total) : "",
  );
  const [usageLimitPerCustomer, setUsageLimitPerCustomer] = useState(
    initial?.usage_limit_per_customer != null ? String(initial.usage_limit_per_customer) : "",
  );
  const [isActive, setIsActive] = useState(initial?.is_active ?? true);

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);

    try {
      await onSubmit({
        code,
        type,
        value: type === "free_shipping" ? null : Number(value),
        min_order_value: minOrderValue ? Number(minOrderValue) : null,
        start_date: startDate || null,
        end_date: endDate || null,
        usage_limit_total: usageLimitTotal ? Number(usageLimitTotal) : null,
        usage_limit_per_customer: usageLimitPerCustomer ? Number(usageLimitPerCustomer) : null,
        is_active: isActive,
      });
    } catch {
      setError(t("errorGeneric"));
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("code")}</span>
        <input value={code} onChange={(event) => setCode(event.target.value.toUpperCase())} required className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("type")}</span>
        <select value={type} onChange={(event) => setType(event.target.value as CouponPayload["type"])} className={inputClass}>
          <option value="percentage">{t("typePercentage")}</option>
          <option value="fixed">{t("typeFixed")}</option>
          <option value="free_shipping">{t("typeFreeShipping")}</option>
        </select>
      </label>

      {type !== "free_shipping" && (
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">
            {type === "percentage" ? `${t("value")} (%)` : `${t("value")} (${t("valueCentimesHint")})`}
          </span>
          <input
            type="number"
            min={1}
            max={type === "percentage" ? 100 : undefined}
            value={value}
            onChange={(event) => setValue(event.target.value)}
            required
            className={inputClass}
          />
        </label>
      )}

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("minOrderValue")}</span>
        <input type="number" min={0} value={minOrderValue} onChange={(event) => setMinOrderValue(event.target.value)} className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("startDate")}</span>
        <input type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("endDate")}</span>
        <input type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("usageLimitTotal")}</span>
        <input
          type="number"
          min={1}
          value={usageLimitTotal}
          onChange={(event) => setUsageLimitTotal(event.target.value)}
          className={inputClass}
        />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("usageLimitPerCustomer")}</span>
        <input
          type="number"
          min={1}
          value={usageLimitPerCustomer}
          onChange={(event) => setUsageLimitPerCustomer(event.target.value)}
          className={inputClass}
        />
      </label>

      <label className="flex items-center gap-2 self-end text-small text-ink/80">
        <input type="checkbox" checked={isActive} onChange={(event) => setIsActive(event.target.checked)} />
        {t("active")}
      </label>

      {error && <p className="col-span-full text-small text-error">{error}</p>}

      <div className="col-span-full flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {submitLabel}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel} className="text-small text-ink/60 hover:text-ink">
            {t("cancel")}
          </button>
        )}
      </div>
    </form>
  );
}

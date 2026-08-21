"use client";

import { useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { adjustInventory } from "@/lib/api";

type Reason = "restock" | "correction" | "return" | "damage";

export function StockAdjust({
  variantId,
  stockQuantity,
  reservedQuantity,
  availableQuantity,
}: {
  variantId: string;
  stockQuantity: number;
  reservedQuantity: number;
  availableQuantity: number;
}) {
  const t = useTranslations("Admin");

  const [stock, setStock] = useState(stockQuantity);
  const [reserved, setReserved] = useState(reservedQuantity);
  const [available, setAvailable] = useState(availableQuantity);
  const [delta, setDelta] = useState("0");
  const [reason, setReason] = useState<Reason>("restock");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);

    try {
      const result = await adjustInventory(variantId, Number(delta), reason);
      setStock(result.stock_quantity);
      setReserved(result.reserved_quantity);
      setAvailable(result.available_quantity);
      setDelta("0");
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="max-w-2xl space-y-4">
      <div className="flex gap-6 text-small text-ink/70">
        <span>
          {t("stockOnHand")}: <strong className="text-ink">{stock}</strong>
        </span>
        <span>
          {t("reserved")}: <strong className="text-ink">{reserved}</strong>
        </span>
        <span>
          {t("available")}: <strong className="text-primary">{available}</strong>
        </span>
      </div>

      <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-3">
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("delta")}</span>
          <input
            type="number"
            value={delta}
            onChange={(event) => setDelta(event.target.value)}
            className="w-28 rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </label>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("reason")}</span>
          <select
            value={reason}
            onChange={(event) => setReason(event.target.value as Reason)}
            className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            <option value="restock">{t("reasonRestock")}</option>
            <option value="correction">{t("reasonCorrection")}</option>
            <option value="return">{t("reasonReturn")}</option>
            <option value="damage">{t("reasonDamage")}</option>
          </select>
        </label>

        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("apply")}
        </button>
      </form>

      {error && <p className="text-small text-error">{error}</p>}
    </div>
  );
}

"use client";

import { useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { updateAdminDeliveryFee, type AdminDeliveryFee } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export function DeliveryFeeRow({ initial }: { initial: AdminDeliveryFee }) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [fee, setFee] = useState(String(initial.fee));
  const [etaMin, setEtaMin] = useState(initial.eta_min_days != null ? String(initial.eta_min_days) : "");
  const [etaMax, setEtaMax] = useState(initial.eta_max_days != null ? String(initial.eta_max_days) : "");
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSaving(true);
    setSaved(false);
    setError(null);
    try {
      await updateAdminDeliveryFee(initial.wilaya_code, {
        fee: Number(fee),
        eta_min_days: etaMin ? Number(etaMin) : null,
        eta_max_days: etaMax ? Number(etaMax) : null,
      });
      setSaved(true);
    } catch {
      // Previously silent — the spinner just stopped with no error and no
      // success message, so a rejected save (e.g. validation error) looked
      // identical to a successful one.
      setError(t("errorGeneric"));
    } finally {
      setSaving(false);
    }
  }

  return (
    <tr className="border-b border-primary/5 last:border-0">
      <td className="px-4 py-2 text-ink">{initial.wilaya_name[locale]}</td>
      <td className="px-4 py-2">
        <form onSubmit={handleSubmit} className="flex flex-wrap items-center gap-2">
          <input
            type="number"
            min={0}
            value={fee}
            onChange={(event) => setFee(event.target.value)}
            className="w-28 rounded-lg border border-primary/15 bg-white px-2 py-1 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
          <input
            type="number"
            min={0}
            placeholder={t("etaMinDays")}
            value={etaMin}
            onChange={(event) => setEtaMin(event.target.value)}
            className="w-24 rounded-lg border border-primary/15 bg-white px-2 py-1 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
          <input
            type="number"
            min={0}
            placeholder={t("etaMaxDays")}
            value={etaMax}
            onChange={(event) => setEtaMax(event.target.value)}
            className="w-24 rounded-lg border border-primary/15 bg-white px-2 py-1 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
          <button
            type="submit"
            disabled={saving}
            className="rounded-full bg-primary px-4 py-1.5 text-caption font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {t("save")}
          </button>
          {saved && <span className="text-caption text-success">{t("saveSuccess")}</span>}
          {error && <span className="text-caption text-error">{error}</span>}
        </form>
      </td>
    </tr>
  );
}

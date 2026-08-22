"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { ApiError, getAdminSettings, updateAdminSettings, type AdminSettings } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function AdminSettingsPage() {
  const t = useTranslations("Admin");

  const [settings, setSettings] = useState<AdminSettings | null>(null);
  const [storeName, setStoreName] = useState("");
  const [supportEmail, setSupportEmail] = useState("");
  const [supportPhone, setSupportPhone] = useState("");
  const [storeAddress, setStoreAddress] = useState("");
  const [taxRatePercent, setTaxRatePercent] = useState("0");
  const [whatsappOrdersEnabled, setWhatsappOrdersEnabled] = useState(true);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    getAdminSettings()
      .then((data) => {
        setSettings(data);
        setStoreName(data.store_name);
        setSupportEmail(data.support_email ?? "");
        setSupportPhone(data.support_phone ?? "");
        setStoreAddress(data.store_address ?? "");
        setTaxRatePercent((data.tax_rate_bps / 100).toString());
        setWhatsappOrdersEnabled(data.whatsapp.orders_enabled);
      })
      .catch(() => setError(t("errorGeneric")))
      .finally(() => setLoading(false));
  }, [t]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaved(false);
    setSaving(true);

    try {
      const updated = await updateAdminSettings({
        store_name: storeName,
        support_email: supportEmail || null,
        support_phone: supportPhone || null,
        store_address: storeAddress || null,
        tax_rate_bps: Math.round(Number(taxRatePercent) * 100),
        whatsapp_orders_enabled: whatsappOrdersEnabled,
      });
      setSettings(updated);
      setSaved(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  return (
    <div className="max-w-lg">
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("settingsTitle")}</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="mb-1 block text-small font-medium text-ink/70">{t("storeName")}</label>
          <input
            type="text"
            required
            value={storeName}
            onChange={(event) => setStoreName(event.target.value)}
            className={inputClass}
          />
        </div>
        <div>
          <label className="mb-1 block text-small font-medium text-ink/70">{t("supportEmail")}</label>
          <input
            type="email"
            value={supportEmail}
            onChange={(event) => setSupportEmail(event.target.value)}
            className={inputClass}
            dir="ltr"
          />
        </div>
        <div>
          <label className="mb-1 block text-small font-medium text-ink/70">{t("supportPhone")}</label>
          <input
            type="tel"
            value={supportPhone}
            onChange={(event) => setSupportPhone(event.target.value)}
            className={inputClass}
            dir="ltr"
          />
        </div>
        <div>
          <label className="mb-1 block text-small font-medium text-ink/70">{t("storeAddress")}</label>
          <input
            type="text"
            value={storeAddress}
            onChange={(event) => setStoreAddress(event.target.value)}
            className={inputClass}
          />
        </div>
        <div>
          <label className="mb-1 block text-small font-medium text-ink/70">{t("taxRate")}</label>
          <input
            type="number"
            min={0}
            max={100}
            step={0.01}
            value={taxRatePercent}
            onChange={(event) => setTaxRatePercent(event.target.value)}
            className={inputClass}
          />
          <p className="mt-1 text-caption text-ink/50">{t("taxRateHint")}</p>
        </div>

        <div>
          <label className="flex items-center gap-2 text-small font-medium text-ink/70">
            <input
              type="checkbox"
              checked={whatsappOrdersEnabled}
              onChange={(event) => setWhatsappOrdersEnabled(event.target.checked)}
              className="h-4 w-4 accent-primary"
            />
            {t("whatsappOrdersEnabled")}
          </label>
          <p className="mt-1 text-caption text-ink/50">{t("whatsappOrdersEnabledHint")}</p>
        </div>

        {error && <p className="text-small text-error">{error}</p>}
        {saved && <p className="text-small text-primary">{t("saveSuccess")}</p>}

        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {saving ? t("loading") : t("save")}
        </button>
      </form>

      {settings && (
        <div className="mt-10">
          <h2 className="mb-3 text-h3 font-semibold text-primary">{t("paymentProviders")}</h2>
          <p className="mb-4 text-caption text-ink/50">{t("paymentProvidersHint")}</p>
          <div className="space-y-2 rounded-2xl bg-white p-4 shadow-soft">
            <ProviderRow label={t("providerCod")} active={settings.payment_providers.cod} t={t} />
            <ProviderRow label={t("providerCib")} active={settings.payment_providers.cib} t={t} />
            <ProviderRow label={t("providerEdahabia")} active={settings.payment_providers.edahabia} t={t} />
          </div>

          <h2 className="mb-3 mt-8 text-h3 font-semibold text-primary">{t("whatsappOrdersSection")}</h2>
          <div className="flex items-center justify-between rounded-2xl bg-white p-4 shadow-soft">
            <span className="text-small text-ink/70" dir="ltr">
              {settings.whatsapp.number ?? "—"}
            </span>
            <Link href="/adminportal/footer/about" className="text-small font-medium text-primary hover:underline">
              {t("manageWhatsAppNumber")}
            </Link>
          </div>
          <p className="mt-1 text-caption text-ink/50">{t("whatsappOrdersNumberHint")}</p>
        </div>
      )}
    </div>
  );
}

function ProviderRow({
  label,
  active,
  t,
}: {
  label: string;
  active: boolean;
  t: ReturnType<typeof useTranslations>;
}) {
  return (
    <div className="flex items-center justify-between border-b border-primary/5 py-2 text-small last:border-0">
      <span className="text-ink/80">{label}</span>
      <span
        className={`rounded-full px-3 py-1 text-caption font-semibold ${
          active ? "bg-primary/10 text-primary" : "bg-ink/5 text-ink/40"
        }`}
      >
        {active ? t("providerActive") : t("providerInactive")}
      </span>
    </div>
  );
}

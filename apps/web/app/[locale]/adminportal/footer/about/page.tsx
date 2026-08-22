"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { ApiError, getAdminSettings, updateAdminSettings, type AdminSettings } from "@/lib/api";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function AdminFooterAboutPage() {
  const t = useTranslations("Admin");

  // store_name/tax_rate_bps aren't shown on this page but must round-trip
  // unchanged — PATCH /adminportal/settings validates them as required (they're
  // owned by the main Settings page, this page only edits about_*/whatsapp_*).
  const [settings, setSettings] = useState<AdminSettings | null>(null);
  const [aboutTitleAr, setAboutTitleAr] = useState("");
  const [aboutTitleFr, setAboutTitleFr] = useState("");
  const [aboutTitleEn, setAboutTitleEn] = useState("");
  const [aboutDescriptionAr, setAboutDescriptionAr] = useState("");
  const [aboutDescriptionFr, setAboutDescriptionFr] = useState("");
  const [aboutDescriptionEn, setAboutDescriptionEn] = useState("");
  const [whatsappNumber, setWhatsappNumber] = useState("");
  const [whatsappMessageAr, setWhatsappMessageAr] = useState("");
  const [whatsappMessageFr, setWhatsappMessageFr] = useState("");
  const [whatsappMessageEn, setWhatsappMessageEn] = useState("");
  const [whatsappActive, setWhatsappActive] = useState(true);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    getAdminSettings()
      .then((data) => {
        setSettings(data);
        setAboutTitleAr(data.about.title.ar ?? "");
        setAboutTitleFr(data.about.title.fr ?? "");
        setAboutTitleEn(data.about.title.en ?? "");
        setAboutDescriptionAr(data.about.description.ar ?? "");
        setAboutDescriptionFr(data.about.description.fr ?? "");
        setAboutDescriptionEn(data.about.description.en ?? "");
        setWhatsappNumber(data.whatsapp.number ?? "");
        setWhatsappMessageAr(data.whatsapp.message.ar ?? "");
        setWhatsappMessageFr(data.whatsapp.message.fr ?? "");
        setWhatsappMessageEn(data.whatsapp.message.en ?? "");
        setWhatsappActive(data.whatsapp.active);
      })
      .catch(() => setError(t("errorGeneric")))
      .finally(() => setLoading(false));
  }, [t]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!settings) return;
    setError(null);
    setSaved(false);
    setSaving(true);

    try {
      await updateAdminSettings({
        store_name: settings.store_name,
        tax_rate_bps: settings.tax_rate_bps,
        about_title_ar: aboutTitleAr,
        about_title_fr: aboutTitleFr,
        about_title_en: aboutTitleEn,
        about_description_ar: aboutDescriptionAr,
        about_description_fr: aboutDescriptionFr,
        about_description_en: aboutDescriptionEn,
        whatsapp_number: whatsappNumber || null,
        whatsapp_message_ar: whatsappMessageAr,
        whatsapp_message_fr: whatsappMessageFr,
        whatsapp_message_en: whatsappMessageEn,
        whatsapp_active: whatsappActive,
      });
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
      <Link href="/adminportal/footer" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("backToFooterSettings")}
      </Link>

      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("footerAboutTitle")}</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutTitleAr")}</span>
          <input value={aboutTitleAr} onChange={(event) => setAboutTitleAr(event.target.value)} dir="rtl" className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutTitleFr")}</span>
          <input value={aboutTitleFr} onChange={(event) => setAboutTitleFr(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutTitleEn")}</span>
          <input value={aboutTitleEn} onChange={(event) => setAboutTitleEn(event.target.value)} className={inputClass} />
        </label>

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutDescriptionAr")}</span>
          <textarea
            value={aboutDescriptionAr}
            onChange={(event) => setAboutDescriptionAr(event.target.value)}
            dir="rtl"
            rows={3}
            className={inputClass}
          />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutDescriptionFr")}</span>
          <textarea value={aboutDescriptionFr} onChange={(event) => setAboutDescriptionFr(event.target.value)} rows={3} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("aboutDescriptionEn")}</span>
          <textarea value={aboutDescriptionEn} onChange={(event) => setAboutDescriptionEn(event.target.value)} rows={3} className={inputClass} />
        </label>

        <hr className="border-primary/10" />

        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("whatsappNumber")}</span>
          <input
            value={whatsappNumber}
            onChange={(event) => setWhatsappNumber(event.target.value)}
            dir="ltr"
            placeholder="213555000000"
            className={inputClass}
          />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("whatsappMessageAr")}</span>
          <input value={whatsappMessageAr} onChange={(event) => setWhatsappMessageAr(event.target.value)} dir="rtl" className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("whatsappMessageFr")}</span>
          <input value={whatsappMessageFr} onChange={(event) => setWhatsappMessageFr(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-small font-medium text-ink/70">{t("whatsappMessageEn")}</span>
          <input value={whatsappMessageEn} onChange={(event) => setWhatsappMessageEn(event.target.value)} className={inputClass} />
        </label>
        <label className="flex items-center gap-2 text-small text-ink/80">
          <input type="checkbox" checked={whatsappActive} onChange={(event) => setWhatsappActive(event.target.checked)} />
          {t("whatsappActive")}
        </label>

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
    </div>
  );
}

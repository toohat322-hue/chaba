"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getCommunes, getWilayas, type Address, type AddressPayload, type Commune, type Wilaya } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export function AddressForm({
  initial,
  onSubmit,
  onCancel,
  submitLabel,
}: {
  initial?: Address;
  onSubmit: (payload: AddressPayload) => Promise<void>;
  onCancel?: () => void;
  submitLabel: string;
}) {
  const t = useTranslations("Checkout");
  const tAuth = useTranslations("Auth");
  const locale = useLocale() as Locale;

  const [wilayas, setWilayas] = useState<Wilaya[]>([]);
  const [communes, setCommunes] = useState<Commune[]>([]);

  const [fullName, setFullName] = useState(initial?.full_name ?? "");
  const [phone, setPhone] = useState(initial?.phone ?? "");
  const [wilayaCode, setWilayaCode] = useState(initial?.wilaya_code ?? "");
  const [communeId, setCommuneId] = useState(initial?.commune_id ?? "");
  const [addressLine, setAddressLine] = useState(initial?.address_line ?? "");
  const [landmark, setLandmark] = useState(initial?.landmark ?? "");
  const [postalCode, setPostalCode] = useState(initial?.postal_code ?? "");
  const [isDefault, setIsDefault] = useState(initial?.is_default ?? false);

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getWilayas()
      .then(setWilayas)
      .catch(() => setWilayas([]));
  }, []);

  useEffect(() => {
    if (!wilayaCode) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- resetting the commune list when the wilaya selection is cleared, not deriving state from a render value
      setCommunes([]);
      return;
    }

    getCommunes(wilayaCode)
      .then(setCommunes)
      .catch(() => setCommunes([]));
  }, [wilayaCode]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await onSubmit({
        full_name: fullName,
        phone,
        wilaya_code: wilayaCode,
        commune_id: communeId,
        address_line: addressLine,
        landmark: landmark || undefined,
        postal_code: postalCode || undefined,
        is_default: isDefault,
      });
    } catch {
      setError(tAuth("saveError"));
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <input
        type="text"
        required
        placeholder={t("fullName")}
        value={fullName}
        onChange={(event) => setFullName(event.target.value)}
        className={inputClass}
      />
      <input
        type="tel"
        required
        placeholder={t("phone")}
        value={phone}
        onChange={(event) => setPhone(event.target.value)}
        className={inputClass}
      />

      <select
        required
        value={wilayaCode}
        onChange={(event) => {
          setWilayaCode(event.target.value);
          setCommuneId("");
        }}
        className={inputClass}
      >
        <option value="" disabled>
          {t("selectWilaya")}
        </option>
        {wilayas.map((wilaya) => (
          <option key={wilaya.code} value={wilaya.code}>
            {wilaya.name[locale]}
          </option>
        ))}
      </select>

      <select
        required
        disabled={!wilayaCode}
        value={communeId}
        onChange={(event) => setCommuneId(event.target.value)}
        className={inputClass}
      >
        <option value="" disabled>
          {t("selectCommune")}
        </option>
        {communes.map((commune) => (
          <option key={commune.id} value={commune.id}>
            {commune.name[locale]}
          </option>
        ))}
      </select>

      <input
        type="text"
        required
        placeholder={t("addressLine")}
        value={addressLine}
        onChange={(event) => setAddressLine(event.target.value)}
        className={inputClass}
      />
      <input
        type="text"
        placeholder={t("landmark")}
        value={landmark}
        onChange={(event) => setLandmark(event.target.value)}
        className={inputClass}
      />
      <input
        type="text"
        placeholder={t("postalCode")}
        value={postalCode}
        onChange={(event) => setPostalCode(event.target.value)}
        className={inputClass}
      />

      <label className="flex items-center gap-2 text-small text-ink/80">
        <input type="checkbox" checked={isDefault} onChange={(event) => setIsDefault(event.target.checked)} />
        {tAuth("setAsDefault")}
      </label>

      {error && <p className="text-small text-error">{error}</p>}

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={submitting}
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {submitLabel}
        </button>
        {onCancel && (
          <button type="button" onClick={onCancel} className="text-small text-ink/60 hover:text-ink">
            {tAuth("cancel")}
          </button>
        )}
      </div>
    </form>
  );
}

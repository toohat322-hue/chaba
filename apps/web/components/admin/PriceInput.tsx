"use client";

import { useLocale } from "next-intl";
import { CURRENCY_LABEL } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

/**
 * Every price in this system is stored/transmitted as integer centimes
 * (PRD A6, avoids float rounding), but an admin typing "10000" to mean
 * "100 DZD" is exactly the kind of thing this field used to require —
 * callers pass/receive a plain DZD string here and convert to/from
 * centimes themselves at load/submit time; this component only renders
 * the input and its currency suffix, in the admin's own current locale.
 */
export function PriceInput({
  value,
  onChange,
  required,
  min = 0,
  className,
  placeholder,
}: {
  value: string;
  onChange: (value: string) => void;
  required?: boolean;
  min?: number;
  className: string;
  placeholder?: string;
}) {
  const locale = useLocale() as Locale;

  return (
    <div className="relative">
      <input
        type="number"
        min={min}
        step="0.01"
        inputMode="decimal"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        required={required}
        placeholder={placeholder}
        className={`${className} pe-12`}
      />
      <span className="pointer-events-none absolute inset-y-0 end-3 flex items-center text-small text-ink/40">
        {CURRENCY_LABEL[locale]}
      </span>
    </div>
  );
}

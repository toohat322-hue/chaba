import { useTranslations } from "next-intl";

function LockIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="h-5 w-5 shrink-0">
      <rect x="5" y="11" width="14" height="9" rx="2" />
      <path d="M8 11V8a4 4 0 0 1 8 0v3" strokeLinecap="round" />
    </svg>
  );
}

function BadgeIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="h-5 w-5 shrink-0">
      <circle cx="12" cy="9" r="6" />
      <path d="M9 14.5L7 21l5-2.5 5 2.5-2-6.5" strokeLinejoin="round" />
    </svg>
  );
}

export function TrustBar() {
  const t = useTranslations("TrustBar");

  // "delivery"/"authentic" moved to the admin-managed Footer features bar
  // (which now appears on every page, not just home) — kept out of here to
  // avoid the same phrase showing twice on the home page.
  const items = [
    { icon: LockIcon, label: t("securePayment") },
    { icon: BadgeIcon, label: t("algerianStore") },
  ];

  return (
    <div className="border-b border-primary/10 bg-primary">
      <div className="mx-auto grid max-w-7xl grid-cols-2 gap-x-4 gap-y-3 px-6 py-4 sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-x-10 sm:gap-y-2 sm:py-3.5">
        {items.map(({ icon: Icon, label }) => (
          <div key={label} className="flex items-center gap-2 text-caption font-medium text-background/85 sm:text-small">
            <Icon />
            <span>{label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

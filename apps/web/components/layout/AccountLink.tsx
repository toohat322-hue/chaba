"use client";

import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";

function UserIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6" strokeLinecap="round" />
    </svg>
  );
}

export function AccountLink() {
  const tNav = useTranslations("Nav");
  const { customer, loading } = useCustomerAuth();

  return (
    <Link
      href={loading || !customer ? "/login" : "/orders"}
      aria-label={tNav("account")}
      className="rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
    >
      <UserIcon />
    </Link>
  );
}

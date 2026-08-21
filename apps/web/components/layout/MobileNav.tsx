"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { Drawer } from "@/components/ui/Drawer";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";

function MenuIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
    </svg>
  );
}

function UserIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-[18px] w-[18px]">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6" strokeLinecap="round" />
    </svg>
  );
}

function HeartIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-[18px] w-[18px]">
      <path
        d="M12 20s-7-4.4-9.5-8.8C.7 7.8 2.3 4 6 4c2 0 3.5 1.2 4.5 2.7C11.5 5.2 13 4 15 4c3.7 0 5.3 3.8 3.5 7.2C19 15.6 12 20 12 20z"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function TrackIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-[18px] w-[18px]">
      <path d="M3 7h11v9H3z" strokeLinejoin="round" />
      <path d="M14 10h4l3 3v3h-7z" strokeLinejoin="round" />
      <circle cx="7.5" cy="18" r="1.5" />
      <circle cx="17.5" cy="18" r="1.5" />
    </svg>
  );
}

export function MobileNav({ items }: { items: { key: string; label: string; href: string }[] }) {
  const t = useTranslations("Nav");
  const tAuth = useTranslations("Auth");
  const tCheckout = useTranslations("Checkout");
  const { customer, loading } = useCustomerAuth();
  const [open, setOpen] = useState(false);

  const quickLinks = [
    {
      key: "account",
      icon: UserIcon,
      label: loading || !customer ? t("account") : tAuth("myOrders"),
      href: loading || !customer ? "/login" : "/orders",
    },
    { key: "wishlist", icon: HeartIcon, label: t("wishlist"), href: "/wishlist" },
    { key: "track", icon: TrackIcon, label: tCheckout("trackTitle"), href: "/track-order" },
  ];

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label={t("menu")}
        className="rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 md:hidden"
      >
        <MenuIcon />
      </button>

      <Drawer open={open} onClose={() => setOpen(false)} title={t("menu")} closeLabel={t("closeMenu")}>
        <nav className="flex flex-col px-2 py-2">
          {items.map((item) => (
            <Link
              key={item.key}
              href={item.href}
              onClick={() => setOpen(false)}
              className="rounded-lg px-4 py-3.5 text-body font-medium text-ink transition-colors hover:bg-primary/5 hover:text-primary"
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="mx-4 border-t border-primary/10" />

        <nav className="flex flex-col px-2 py-2">
          {quickLinks.map((item) => (
            <Link
              key={item.key}
              href={item.href}
              onClick={() => setOpen(false)}
              className="flex items-center gap-3 rounded-lg px-4 py-3.5 text-body font-medium text-ink/80 transition-colors hover:bg-primary/5 hover:text-primary"
            >
              <item.icon />
              {item.label}
            </Link>
          ))}
        </nav>
      </Drawer>
    </>
  );
}

"use client";

import Image from "next/image";
import { useEffect, useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { AccountLink } from "./AccountLink";
import { CartBadge } from "./CartBadge";
import { WishlistBadge } from "./WishlistBadge";
import { MobileNav } from "./MobileNav";
import { SearchOverlay } from "./SearchOverlay";
import { LanguageSwitcher } from "./LanguageSwitcher";
import { NotificationBell } from "@/components/notifications/NotificationBell";
import { useCartDrawer } from "@/components/cart/CartDrawerProvider";

function SearchIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-4.3-4.3" strokeLinecap="round" />
    </svg>
  );
}

function HeartIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path
        d="M12 20s-7-4.4-9.5-8.8C.7 7.8 2.3 4 6 4c2 0 3.5 1.2 4.5 2.7C11.5 5.2 13 4 15 4c3.7 0 5.3 3.8 3.5 7.2C19 15.6 12 20 12 20z"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function CartIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.85" className="h-[22px] w-[22px]">
      <path d="M4 6h2l1.6 10.2A2 2 0 0 0 9.6 18h7.8a2 2 0 0 0 2-1.6L21 9H6" strokeLinecap="round" strokeLinejoin="round" />
      <circle cx="10" cy="21" r="1.2" fill="currentColor" stroke="none" />
      <circle cx="18" cy="21" r="1.2" fill="currentColor" stroke="none" />
    </svg>
  );
}

const focusRing =
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/60 focus-visible:ring-offset-2 focus-visible:ring-offset-primary";

export function SiteHeader() {
  const tHome = useTranslations("Home");
  const tNav = useTranslations("Nav");
  const { open: openCartDrawer } = useCartDrawer();
  const [searchOpen, setSearchOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [hidden, setHidden] = useState(false);
  const lastYRef = useRef(0);

  const navItems = [
    { key: "home", label: tNav("home"), href: "/" },
    { key: "saudiPerfumes", label: tNav("saudiPerfumes"), href: "/category/saudi-perfumes" },
    { key: "turkishPerfumes", label: tNav("turkishPerfumes"), href: "/category/turkish-perfumes" },
    { key: "oudOil", label: tNav("oudOil"), href: "/category/oud-oil" },
    { key: "exclusiveOffers", label: tNav("exclusiveOffers"), href: "/category/exclusive-offers" },
  ];

  // "Smart" sticky header: recedes on scroll-down past the fold, reappears
  // on scroll-up, and only picks up a shadow once the page has actually
  // scrolled — keeps the flat top-of-page look intact.
  useEffect(() => {
    lastYRef.current = window.scrollY;
    let ticking = false;

    function update() {
      const y = window.scrollY;
      const lastY = lastYRef.current;
      setScrolled(y > 8);
      setHidden((current) => (searchOpen ? false : y > lastY && y > 96 ? true : y < lastY ? false : current));
      lastYRef.current = y;
      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        requestAnimationFrame(update);
        ticking = true;
      }
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [searchOpen]);

  function handleOpenSearch() {
    setSearchOpen(true);
    setHidden(false);
  }

  return (
    <>
      <header
        className={`sticky top-0 z-50 border-b border-background/10 bg-primary/97 backdrop-blur transition-transform duration-300 motion-reduce:transition-none ${
          hidden ? "-translate-y-full" : "translate-y-0"
        } ${scrolled ? "shadow-lift" : ""}`}
      >
        <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-1">
            <MobileNav items={navItems} />
            <Link href="/" className={`shrink-0 overflow-hidden rounded-lg ${focusRing}`}>
              <Image
                src="/brand/logo.png"
                alt={tHome("title")}
                width={48}
                height={48}
                priority
                className="h-12 w-12 rounded-lg object-cover ring-1 ring-background/15"
              />
            </Link>
          </div>

          <nav className="hidden items-center gap-8 md:flex">
            {navItems.map((item) => (
              <Link
                key={item.key}
                href={item.href}
                className={`rounded text-small font-medium text-background/85 transition-colors hover:text-accent ${focusRing}`}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-0.5 sm:gap-1.5">
            <button
              type="button"
              onClick={handleOpenSearch}
              aria-label={tNav("searchSubmit")}
              className={`rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent ${focusRing}`}
            >
              <SearchIcon />
            </button>

            <Link
              href="/wishlist"
              aria-label={tNav("wishlist")}
              className={`relative rounded-full p-2.5 text-background/85 transition-colors hover:bg-background/10 hover:text-accent ${focusRing}`}
            >
              <HeartIcon />
              <WishlistBadge />
            </Link>

            <NotificationBell />

            <button
              type="button"
              onClick={openCartDrawer}
              aria-label={tNav("cart")}
              className={`relative rounded-full bg-background/10 p-2.5 text-accent transition-colors hover:bg-background/15 ${focusRing}`}
            >
              <CartIcon />
              <CartBadge />
            </button>

            <AccountLink />
            <LanguageSwitcher />
          </div>
        </div>
      </header>

      <SearchOverlay open={searchOpen} onClose={() => setSearchOpen(false)} />
    </>
  );
}

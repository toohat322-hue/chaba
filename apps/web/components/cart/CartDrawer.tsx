"use client";

import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { Drawer } from "@/components/ui/Drawer";
import { useCart } from "@/components/cart/CartProvider";
import { useCartDrawer } from "@/components/cart/CartDrawerProvider";
import { CartLineItem } from "@/components/cart/CartLineItem";
import { CartTotals } from "@/components/cart/CartTotals";
import { ShabaLoader } from "@/components/ui/ShabaLoader";

type Locale = "ar" | "fr" | "en";

export function CartDrawer() {
  const locale = useLocale() as Locale;
  const t = useTranslations("Cart");
  const tLoading = useTranslations("Loading");
  const { cart, loading } = useCart();
  const { isOpen, close } = useCartDrawer();

  const items = cart?.items ?? [];
  const hasItems = items.length > 0;

  return (
    <Drawer open={isOpen} onClose={close} title={t("title")} closeLabel={t("close")}>
      {loading ? (
        <ShabaLoader label={tLoading("label")} />
      ) : hasItems && cart ? (
        <div className="flex h-full flex-col">
          <div className="flex-1 overflow-y-auto px-6">
            {items.map((item) => (
              <CartLineItem key={item.id} item={item} compact />
            ))}
          </div>

          <div className="border-t border-primary/10 px-6 py-5">
            <CartTotals
              subtotal={cart.subtotal}
              discount={cart.discount_total}
              total={cart.subtotal - cart.discount_total}
              locale={locale}
              size="compact"
            />

            <div className="mt-4 flex flex-col gap-2">
              <Link
                href="/checkout"
                onClick={close}
                className="flex w-full items-center justify-center rounded-full bg-primary px-6 py-3 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
              >
                {t("checkout")}
              </Link>
              <Link
                href="/cart"
                onClick={close}
                className="flex w-full items-center justify-center rounded-full border border-primary/20 px-6 py-3 text-body font-medium text-ink transition-colors hover:border-primary/40 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
              >
                {t("viewCart")}
              </Link>
            </div>
          </div>
        </div>
      ) : (
        <div className="flex h-full flex-col items-center justify-center gap-4 px-6 text-center">
          <p className="text-body text-ink/60">{t("empty")}</p>
          <Link
            href="/"
            onClick={close}
            className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
          >
            {t("browseCategories")}
          </Link>
        </div>
      )}
    </Drawer>
  );
}

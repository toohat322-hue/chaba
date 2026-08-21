"use client";

import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/components/cart/CartProvider";
import { CartLineItem } from "@/components/cart/CartLineItem";
import { CartTotals } from "@/components/cart/CartTotals";
import { CouponForm } from "@/components/cart/CouponForm";
import { ShabaLoader } from "@/components/ui/ShabaLoader";

type Locale = "ar" | "fr" | "en";

export default function CartPage() {
  const locale = useLocale() as Locale;
  const t = useTranslations("Cart");
  const { cart, loading } = useCart();
  const tLoading = useTranslations("Loading");

  if (loading) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto flex max-w-3xl flex-col items-center gap-4 px-6 py-24 text-center">
        <h1 className="text-h1 font-semibold text-primary">{t("title")}</h1>
        <p className="text-body text-ink/60">{t("empty")}</p>
        <Link
          href="/"
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
        >
          {t("browseCategories")}
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl px-6 py-12">
      <h1 className="mb-8 text-h1 font-semibold text-primary">{t("title")}</h1>

      <div className="rounded-2xl bg-white px-6 shadow-soft">
        {cart.items.map((item) => (
          <CartLineItem key={item.id} item={item} />
        ))}
      </div>

      <div className="mt-6">
        <CouponForm coupon={cart.coupon} />
      </div>

      <div className="mt-6 rounded-2xl bg-white p-6 shadow-soft">
        <CartTotals
          subtotal={cart.subtotal}
          discount={cart.discount_total}
          total={cart.subtotal - cart.discount_total}
          locale={locale}
        />

        <Link
          href="/checkout"
          className="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3.5 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
        >
          {t("checkout")}
        </Link>
      </div>
    </div>
  );
}

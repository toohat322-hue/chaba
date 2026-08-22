"use client";

import { useEffect } from "react";
import { useTranslations } from "next-intl";
import { Link, usePathname, useRouter } from "@/i18n/navigation";
import { LanguageSwitcher } from "@/components/layout/LanguageSwitcher";
import { useAdminAuth } from "./AdminAuthProvider";

const NAV_ITEMS = [
  { key: "navDashboard", href: "/adminportal" },
  { key: "navProducts", href: "/adminportal/products" },
  { key: "navCategories", href: "/adminportal/categories" },
  { key: "navOrders", href: "/adminportal/orders" },
  { key: "navDeliveryFees", href: "/adminportal/delivery-fees" },
  { key: "navCoupons", href: "/adminportal/coupons" },
  { key: "navPayments", href: "/adminportal/payments" },
  { key: "navReviews", href: "/adminportal/reviews" },
  { key: "navCustomers", href: "/adminportal/customers" },
  { key: "navRoles", href: "/adminportal/roles" },
  { key: "navStaff", href: "/adminportal/staff" },
  { key: "navSettings", href: "/adminportal/settings" },
  { key: "navFooterSettings", href: "/adminportal/footer" },
  { key: "navHeroSlider", href: "/adminportal/hero-slider" },
  { key: "navSecurity", href: "/adminportal/security" },
  { key: "navAuditLog", href: "/adminportal/audit-log" },
] as const;

export function AdminShell({ children }: { children: React.ReactNode }) {
  const t = useTranslations("Admin");
  const pathname = usePathname();
  const router = useRouter();
  const { admin, loading, logout } = useAdminAuth();

  const isLoginPage = pathname === "/adminportal/login";

  useEffect(() => {
    if (!loading && !admin && !isLoginPage) {
      router.replace("/adminportal/login");
    }
  }, [loading, admin, isLoginPage, router]);

  if (isLoginPage) {
    return <>{children}</>;
  }

  if (loading || !admin) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <p className="text-body text-ink/50">{t("loading")}</p>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-background">
      <aside className="flex w-60 shrink-0 flex-col bg-primary px-4 py-6">
        <div className="mb-8 px-2 text-h3 font-semibold text-background">{t("dashboardTitle")}</div>

        <nav className="flex flex-1 flex-col gap-1">
          {NAV_ITEMS.map((item) => {
            const active =
              pathname === item.href || (item.href !== "/adminportal" && pathname.startsWith(`${item.href}/`));
            return (
              <Link
                key={item.key}
                href={item.href}
                className={`rounded-lg px-3 py-2 text-small font-medium transition-colors ${
                  active
                    ? "bg-background/15 text-background"
                    : "text-background/70 hover:bg-background/10 hover:text-background"
                }`}
              >
                {t(item.key)}
              </Link>
            );
          })}
        </nav>

        <div className="mt-auto flex items-center justify-between gap-2 px-1">
          <button
            type="button"
            onClick={() => {
              void logout().then(() => router.replace("/adminportal/login"));
            }}
            className="rounded-lg px-2 py-2 text-start text-small font-medium text-background/70 transition-colors hover:bg-background/10 hover:text-background"
          >
            {t("logout")}
          </button>
          <LanguageSwitcher />
        </div>
      </aside>

      <main className="flex-1 overflow-y-auto px-8 py-8">{children}</main>
    </div>
  );
}

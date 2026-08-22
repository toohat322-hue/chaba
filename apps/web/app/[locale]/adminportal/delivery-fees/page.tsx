"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { getAdminDeliveryFees, type AdminDeliveryFee } from "@/lib/api";
import { DeliveryFeeRow } from "@/components/admin/DeliveryFeeRow";

export default function AdminDeliveryFeesPage() {
  const t = useTranslations("Admin");

  const [fees, setFees] = useState<AdminDeliveryFee[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getAdminDeliveryFees()
      .then(setFees)
      .finally(() => setLoading(false));
  }, []);

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("deliveryFeesTitle")}</h1>

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : (
        <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
          <table className="w-full text-start text-small">
            <thead>
              <tr className="border-b border-primary/10 text-ink/60">
                <th className="px-4 py-3 text-start font-medium">{t("name")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("price")}</th>
              </tr>
            </thead>
            <tbody>
              {fees.map((fee) => (
                <DeliveryFeeRow key={fee.wilaya_code} initial={fee} />
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

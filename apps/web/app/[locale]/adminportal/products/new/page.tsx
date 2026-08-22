"use client";

import { useTranslations } from "next-intl";
import { ProductForm } from "@/components/admin/ProductForm";

export default function NewAdminProductPage() {
  const t = useTranslations("Admin");

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("newProduct")}</h1>
      <ProductForm />
    </div>
  );
}

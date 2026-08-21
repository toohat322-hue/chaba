"use client";

import { useTranslations } from "next-intl";
import { CategoryForm } from "@/components/admin/CategoryForm";

export default function NewAdminCategoryPage() {
  const t = useTranslations("Admin");

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{t("newCategory")}</h1>
      <CategoryForm />
    </div>
  );
}

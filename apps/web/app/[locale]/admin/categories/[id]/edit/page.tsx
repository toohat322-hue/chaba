"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { getAdminCategories, type AdminCategory } from "@/lib/api";
import { CategoryForm } from "@/components/admin/CategoryForm";

type Locale = "ar" | "fr" | "en";

export default function EditAdminCategoryPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const params = useParams<{ id: string }>();
  const categoryId = params.id;

  const [category, setCategory] = useState<AdminCategory | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getAdminCategories()
      .then((all) => setCategory(all.find((item) => item.id === categoryId) ?? null))
      .finally(() => setLoading(false));
  }, [categoryId]);

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (!category) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  return (
    <div>
      <h1 className="mb-6 text-h1 font-semibold text-primary">{category.name[locale]}</h1>
      <CategoryForm categoryId={category.id} initial={category} />
    </div>
  );
}

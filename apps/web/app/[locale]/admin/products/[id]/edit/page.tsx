"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { ApiError, getAdminProduct, type AdminProduct } from "@/lib/api";
import { ProductForm } from "@/components/admin/ProductForm";
import { ProductImages } from "@/components/admin/ProductImages";
import { VariantManager } from "@/components/admin/VariantManager";

export default function EditAdminProductPage() {
  const t = useTranslations("Admin");
  const params = useParams<{ id: string }>();
  const productId = params.id;

  const [product, setProduct] = useState<AdminProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    getAdminProduct(productId)
      .then(setProduct)
      .catch((error) => {
        if (error instanceof ApiError && error.status === 404) {
          setNotFound(true);
        }
      })
      .finally(() => setLoading(false));
  }, [productId]);

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (notFound || !product) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  return (
    <div className="space-y-10">
      <h1 className="text-h1 font-semibold text-primary">{product.name.ar}</h1>

      <div className="max-w-2xl rounded-2xl bg-white p-6 shadow-soft">
        <h2 className="mb-4 text-h3 font-semibold text-ink">{t("images")}</h2>
        <ProductImages productId={product.id} initial={product.images} />
      </div>

      <ProductForm productId={product.id} initial={product} />

      <div>
        <h2 className="mb-3 text-h3 font-semibold text-ink">{t("variantsTitle")}</h2>
        <VariantManager productId={product.id} initial={product.variants} />
      </div>
    </div>
  );
}

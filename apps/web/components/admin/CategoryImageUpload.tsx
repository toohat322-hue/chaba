"use client";

import { useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { deleteCategoryImage, uploadCategoryImage, type AdminCategory } from "@/lib/api";

export function CategoryImageUpload({
  categoryId,
  imageUrl,
  onChange,
}: {
  categoryId: string;
  imageUrl: string | null;
  onChange: (category: AdminCategory) => void;
}) {
  const t = useTranslations("Admin");
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleFileChange(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;

    setError(null);
    setBusy(true);
    try {
      const category = await uploadCategoryImage(categoryId, file);
      onChange(category);
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setBusy(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  }

  async function handleRemove() {
    setError(null);
    setBusy(true);
    try {
      const category = await deleteCategoryImage(categoryId);
      onChange(category);
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div>
      <span className="mb-1 block text-caption text-ink/60">{t("categoryImage")}</span>
      <div className="flex items-center gap-3">
        {imageUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={imageUrl} alt="" className="h-16 w-16 rounded-md border border-primary/10 object-cover" />
        ) : (
          <span className="flex h-16 w-16 items-center justify-center rounded-md border border-dashed border-primary/20 text-caption text-ink/40">
            {t("noImage")}
          </span>
        )}
        <div className="flex flex-col gap-1">
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            onChange={handleFileChange}
            disabled={busy}
            className="text-caption"
          />
          {imageUrl && (
            <button
              type="button"
              onClick={handleRemove}
              disabled={busy}
              className="self-start text-caption text-error hover:underline disabled:opacity-50"
            >
              {t("delete")}
            </button>
          )}
        </div>
      </div>
      {error && <p className="mt-1 text-caption text-error">{error}</p>}
    </div>
  );
}

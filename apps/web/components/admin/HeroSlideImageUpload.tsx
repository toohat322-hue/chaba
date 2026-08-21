"use client";

import { useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { uploadHeroSlideImage, type AdminHeroSlide } from "@/lib/api";

export function HeroSlideImageUpload({
  slideId,
  variant,
  imageUrl,
  onUploaded,
}: {
  slideId: string;
  variant: "desktop" | "mobile";
  imageUrl: string | null;
  onUploaded: (slide: AdminHeroSlide) => void;
}) {
  const t = useTranslations("Admin");
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleFileChange(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;

    setError(null);
    setUploading(true);
    try {
      const slide = await uploadHeroSlideImage(slideId, file, variant);
      onUploaded(slide);
    } catch {
      setError(t("errorGeneric"));
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  }

  return (
    <div>
      <span className="mb-1 block text-caption text-ink/60">
        {variant === "desktop" ? t("heroSlideDesktopImage") : t("heroSlideMobileImage")}
      </span>
      <div className="flex items-center gap-3">
        {imageUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={imageUrl} alt="" className="h-16 w-28 rounded-md border border-primary/10 object-cover" />
        ) : (
          <span className="flex h-16 w-28 items-center justify-center rounded-md border border-dashed border-primary/20 text-caption text-ink/40">
            {t("noImage")}
          </span>
        )}
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          onChange={handleFileChange}
          disabled={uploading}
          className="text-caption"
        />
      </div>
      {error && <p className="mt-1 text-caption text-error">{error}</p>}
    </div>
  );
}

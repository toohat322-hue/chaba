"use client";

import { useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import type { AdminHeroSlide, HeroSlidePayload } from "@/lib/api";
import { HeroSlideProductPicker, type PickedProduct } from "./HeroSlideProductPicker";
import { HeroSlideImageUpload } from "./HeroSlideImageUpload";
import { HeroSlidePreview } from "./HeroSlidePreview";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

function toDateInput(value: string | null): string {
  return value ? value.slice(0, 10) : "";
}

export function HeroSlideForm({
  initial,
  onSubmit,
  onCancel,
  submitLabel,
  onImageUploaded,
}: {
  initial?: AdminHeroSlide;
  onSubmit: (payload: HeroSlidePayload) => Promise<void>;
  onCancel?: () => void;
  submitLabel: string;
  onImageUploaded?: (slide: AdminHeroSlide) => void;
}) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [product, setProduct] = useState<PickedProduct | null>(
    initial?.product
      ? { id: initial.product.id, name: initial.product.name[locale], image_url: initial.product.image_url }
      : null,
  );
  const [titleAr, setTitleAr] = useState(initial?.title.ar ?? "");
  const [titleFr, setTitleFr] = useState(initial?.title.fr ?? "");
  const [titleEn, setTitleEn] = useState(initial?.title.en ?? "");
  const [subtitleAr, setSubtitleAr] = useState(initial?.subtitle.ar ?? "");
  const [subtitleFr, setSubtitleFr] = useState(initial?.subtitle.fr ?? "");
  const [subtitleEn, setSubtitleEn] = useState(initial?.subtitle.en ?? "");
  const [ctaAr, setCtaAr] = useState(initial?.cta_label.ar ?? "");
  const [ctaFr, setCtaFr] = useState(initial?.cta_label.fr ?? "");
  const [ctaEn, setCtaEn] = useState(initial?.cta_label.en ?? "");
  const [isActive, setIsActive] = useState(initial?.is_active ?? true);
  const [startDate, setStartDate] = useState(toDateInput(initial?.start_date ?? null));
  const [endDate, setEndDate] = useState(toDateInput(initial?.end_date ?? null));

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);

    if (!product) {
      setError(t("heroSlideProductRequired"));
      return;
    }

    setSaving(true);
    try {
      await onSubmit({
        product_id: product.id,
        title_ar: titleAr || null,
        title_fr: titleFr || null,
        title_en: titleEn || null,
        subtitle_ar: subtitleAr || null,
        subtitle_fr: subtitleFr || null,
        subtitle_en: subtitleEn || null,
        cta_label_ar: ctaAr || null,
        cta_label_fr: ctaFr || null,
        cta_label_en: ctaEn || null,
        is_active: isActive,
        start_date: startDate || null,
        end_date: endDate || null,
      });
    } catch {
      setError(t("errorGeneric"));
      setSaving(false);
    }
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
      <form onSubmit={handleSubmit} className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div className="col-span-full">
          <span className="mb-1 block text-caption text-ink/60">{t("product")}</span>
          <HeroSlideProductPicker value={product} onChange={setProduct} />
        </div>

        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("startDate")}</span>
          <input type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("endDate")}</span>
          <input type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} className={inputClass} />
        </label>
        <label className="flex items-center gap-2 self-end text-small text-ink/80">
          <input type="checkbox" checked={isActive} onChange={(event) => setIsActive(event.target.checked)} />
          {t("active")}
        </label>

        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideTitleAr")}</span>
          <input value={titleAr} onChange={(event) => setTitleAr(event.target.value)} dir="rtl" className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideTitleFr")}</span>
          <input value={titleFr} onChange={(event) => setTitleFr(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideTitleEn")}</span>
          <input value={titleEn} onChange={(event) => setTitleEn(event.target.value)} className={inputClass} />
        </label>

        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideSubtitleAr")}</span>
          <input value={subtitleAr} onChange={(event) => setSubtitleAr(event.target.value)} dir="rtl" className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideSubtitleFr")}</span>
          <input value={subtitleFr} onChange={(event) => setSubtitleFr(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideSubtitleEn")}</span>
          <input value={subtitleEn} onChange={(event) => setSubtitleEn(event.target.value)} className={inputClass} />
        </label>

        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideCtaAr")}</span>
          <input value={ctaAr} onChange={(event) => setCtaAr(event.target.value)} dir="rtl" className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideCtaFr")}</span>
          <input value={ctaFr} onChange={(event) => setCtaFr(event.target.value)} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1 block text-caption text-ink/60">{t("heroSlideCtaEn")}</span>
          <input value={ctaEn} onChange={(event) => setCtaEn(event.target.value)} className={inputClass} />
        </label>

        {initial && (
          <div className="col-span-full grid gap-3 border-t border-primary/10 pt-3 sm:grid-cols-2">
            <HeroSlideImageUpload
              slideId={initial.id}
              variant="desktop"
              imageUrl={initial.image_url}
              onUploaded={(slide) => onImageUploaded?.(slide)}
            />
            <HeroSlideImageUpload
              slideId={initial.id}
              variant="mobile"
              imageUrl={initial.mobile_image_url}
              onUploaded={(slide) => onImageUploaded?.(slide)}
            />
          </div>
        )}

        {error && <p className="col-span-full text-small text-error">{error}</p>}

        <div className="col-span-full flex gap-3">
          <button
            type="submit"
            disabled={saving}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {submitLabel}
          </button>
          {onCancel && (
            <button type="button" onClick={onCancel} className="text-small text-ink/60 hover:text-ink">
              {t("cancel")}
            </button>
          )}
        </div>
      </form>

      <div className="rounded-xl border border-primary/10 bg-primary/5 p-3">
        <p className="mb-2 text-caption font-semibold text-ink/70">{t("heroSlidePreviewTitle")}</p>
        <HeroSlidePreview
          product={product}
          title={{ ar: titleAr, fr: titleFr, en: titleEn }}
          subtitle={{ ar: subtitleAr, fr: subtitleFr, en: subtitleEn }}
          ctaLabel={{ ar: ctaAr, fr: ctaFr, en: ctaEn }}
          imageUrl={initial?.image_url ?? null}
          mobileImageUrl={initial?.mobile_image_url ?? null}
        />
      </div>
    </div>
  );
}

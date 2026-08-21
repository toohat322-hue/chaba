"use client";

import { useLocale, useTranslations } from "next-intl";
import type { HeroSlide } from "@/lib/api";
import { HeroSlideVisual } from "@/components/home/HeroSlideVisual";
import type { PickedProduct } from "./HeroSlideProductPicker";

type Locale = "ar" | "fr" | "en";

const FALLBACK_CTA: Record<Locale, string> = { ar: "تسوّق الآن", fr: "Achetez maintenant", en: "Shop Now" };

/**
 * Live preview (point 18) — built from the form's current field values, not
 * the last-saved slide, so it updates as the admin types rather than only
 * after hitting Save. Mirrors the public controller's title/CTA fallback
 * logic client-side so an untitled draft still previews sensibly.
 */
export function HeroSlidePreview({
  product,
  title,
  subtitle,
  ctaLabel,
  imageUrl,
  mobileImageUrl,
}: {
  product: PickedProduct | null;
  title: Record<Locale, string>;
  subtitle: Record<Locale, string>;
  ctaLabel: Record<Locale, string>;
  imageUrl: string | null;
  mobileImageUrl: string | null;
}) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  if (!product) {
    return <p className="text-small text-ink/50">{t("heroSlidePreviewNeedsProduct")}</p>;
  }

  const slide: HeroSlide = {
    id: "preview",
    product: { slug: "", name: { ar: product.name, fr: product.name, en: product.name } },
    image_url: imageUrl,
    mobile_image_url: mobileImageUrl,
    title: {
      ar: title.ar || product.name,
      fr: title.fr || product.name,
      en: title.en || product.name,
    },
    subtitle: { ar: subtitle.ar || null, fr: subtitle.fr || null, en: subtitle.en || null },
    cta_label: {
      ar: ctaLabel.ar || FALLBACK_CTA.ar,
      fr: ctaLabel.fr || FALLBACK_CTA.fr,
      en: ctaLabel.en || FALLBACK_CTA.en,
    },
  };

  return (
    <div className="space-y-3">
      <div>
        <p className="mb-1 text-caption text-ink/60">{t("heroSlidePreviewDesktop")}</p>
        <div className="h-56 w-full overflow-hidden rounded-lg">
          <HeroSlideVisual slide={slide} locale={locale} interactive={false} animate={false} />
        </div>
      </div>
      <div>
        <p className="mb-1 text-caption text-ink/60">{t("heroSlidePreviewMobile")}</p>
        <div className="h-56 w-40 overflow-hidden rounded-lg">
          <HeroSlideVisual slide={slide} locale={locale} interactive={false} animate={false} />
        </div>
      </div>
    </div>
  );
}

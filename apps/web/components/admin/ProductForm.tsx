"use client";

import { useEffect, useRef, useState, type FormEvent, type ReactNode } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import {
  createAdminProduct,
  createProductVariant,
  getAdminCategories,
  updateAdminProduct,
  uploadProductImage,
  type AdminCategory,
  type AdminProduct,
  type CreateProductPayload,
} from "@/lib/api";
import { adminErrorMessage, adminFieldErrors } from "@/lib/adminErrors";
import { compressImageFile } from "@/lib/imageCompression";
import { useToast } from "@/components/ui/Toast";
import { ImageDropzone, type DropzoneImage } from "./ImageDropzone";
import { PriceInput } from "./PriceInput";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";
const inputErrorClass = "border-error focus:ring-error/30";

const smallInputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-2.5 py-1.5 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

const QUICK_SIZES = [10, 20, 50, 100] as const;

type QuickSizeState = { checked: boolean; price: string; stock: string; sku: string };
type StagedImage = { localId: string; file: File; previewUrl: string };
type UploadStatus = "uploading" | "uploaded" | "failed";

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-small font-medium text-ink/70">{label}</span>
      {children}
      {error && <span className="mt-1 block text-caption text-error">{error}</span>}
    </label>
  );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="rounded-2xl bg-white p-6 shadow-soft">
      <h2 className="mb-4 text-h3 font-semibold text-ink">{title}</h2>
      <div className="space-y-5">{children}</div>
    </div>
  );
}

export function ProductForm({ productId, initial }: { productId?: string; initial?: AdminProduct }) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const router = useRouter();
  const toast = useToast();

  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [categoriesLoading, setCategoriesLoading] = useState(true);
  const [categoryId, setCategoryId] = useState(initial?.category_id ?? "");
  const [nameAr, setNameAr] = useState(initial?.name.ar ?? "");
  const [nameFr, setNameFr] = useState(initial?.name.fr ?? "");
  const [nameEn, setNameEn] = useState(initial?.name.en ?? "");
  const [descAr, setDescAr] = useState(initial?.description.ar ?? "");
  const [descFr, setDescFr] = useState(initial?.description.fr ?? "");
  const [descEn, setDescEn] = useState(initial?.description.en ?? "");
  // Displayed/entered in plain DZD, not the centimes the API stores/expects
  // (PriceInput is presentation-only) — converted back to centimes at
  // submit time below.
  const [basePrice, setBasePrice] = useState(initial ? String(initial.base_price / 100) : "");
  const [comparePrice, setComparePrice] = useState(
    initial?.compare_at_price != null ? String(initial.compare_at_price / 100) : "",
  );
  const [status, setStatus] = useState<AdminProduct["status"]>(initial?.status ?? "draft");
  const [featured, setFeatured] = useState(initial?.featured ?? false);
  const [seoTitle, setSeoTitle] = useState(initial?.seo_title ?? "");
  const [seoDescription, setSeoDescription] = useState(initial?.seo_description ?? "");
  const [sku, setSku] = useState("");
  const [initialStock, setInitialStock] = useState("0");
  const [lowStockThreshold, setLowStockThreshold] = useState("5");
  const [sizeValue, setSizeValue] = useState("");
  const [sizeUnit, setSizeUnit] = useState("ml");
  const [quickSizes, setQuickSizes] = useState<Record<number, QuickSizeState>>(
    Object.fromEntries(QUICK_SIZES.map((size) => [size, { checked: false, price: "", stock: "0", sku: "" }])),
  );

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  // Images picked before the product exists yet — staged in the browser
  // only (preview via object URL), uploaded once the product is created so
  // the admin experiences "pick images, fill the form, save" as one step.
  const stagedIdRef = useRef(0);
  const [stagedImages, setStagedImages] = useState<StagedImage[]>([]);
  const [createdProductId, setCreatedProductId] = useState<string | null>(null);
  const [imageUploadStatuses, setImageUploadStatuses] = useState<Record<string, UploadStatus>>({});

  function updateQuickSize(size: number, patch: Partial<QuickSizeState>) {
    setQuickSizes((prev) => ({ ...prev, [size]: { ...prev[size], ...patch } }));
  }

  useEffect(() => {
    getAdminCategories()
      .then(setCategories)
      .catch(() => setCategories([]))
      .finally(() => setCategoriesLoading(false));
  }, []);

  function handleImagesSelected(files: File[]) {
    const next = files.map((file) => ({
      localId: `staged-${stagedIdRef.current++}`,
      file,
      previewUrl: URL.createObjectURL(file),
    }));
    setStagedImages((prev) => [...prev, ...next]);
  }

  function handleDeleteStagedImage(id: string) {
    setStagedImages((prev) => {
      const target = prev.find((s) => s.localId === id);
      if (target) URL.revokeObjectURL(target.previewUrl);
      return prev.filter((s) => s.localId !== id);
    });
  }

  function handleReorderStagedImages(orderedIds: string[]) {
    setStagedImages((prev) =>
      orderedIds.map((id) => prev.find((s) => s.localId === id)).filter((s): s is StagedImage => s !== undefined),
    );
  }

  async function uploadStagedImage(newProductId: string, staged: StagedImage): Promise<boolean> {
    setImageUploadStatuses((prev) => ({ ...prev, [staged.localId]: "uploading" }));
    try {
      const compressed = await compressImageFile(staged.file);
      await uploadProductImage(newProductId, compressed);
      setImageUploadStatuses((prev) => ({ ...prev, [staged.localId]: "uploaded" }));
      return true;
    } catch (err) {
      setImageUploadStatuses((prev) => ({ ...prev, [staged.localId]: "failed" }));
      setError(adminErrorMessage(err, t));
      return false;
    }
  }

  function handleRetryStagedImage(localId: string) {
    if (!createdProductId) return;
    const staged = stagedImages.find((s) => s.localId === localId);
    if (staged) void uploadStagedImage(createdProductId, staged);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setFieldErrors({});
    setSaving(true);

    try {
      const shared = {
        category_id: categoryId,
        name_ar: nameAr,
        name_fr: nameFr,
        name_en: nameEn,
        description_ar: descAr,
        description_fr: descFr,
        description_en: descEn,
        base_price: Math.round(Number(basePrice) * 100),
        compare_at_price: comparePrice ? Math.round(Number(comparePrice) * 100) : null,
        status,
        featured,
        seo_title: seoTitle || undefined,
        seo_description: seoDescription || undefined,
      };

      if (productId) {
        await updateAdminProduct(productId, shared);
        toast.show({ message: t("saveSuccess") });
        router.push("/adminportal/products");
        return;
      }

      const payload: CreateProductPayload = {
        ...shared,
        sku,
        initial_stock: Number(initialStock),
        low_stock_threshold: Number(lowStockThreshold),
        size_value: sizeValue ? Number(sizeValue) : null,
        size_unit: sizeValue ? sizeUnit : null,
      };
      const created = await createAdminProduct(payload);

      // Quick-add sizes (optional, never mandatory): one createProductVariant
      // call per checked size, reusing the same endpoint VariantManager uses
      // on the edit page, not a new one.
      const checkedSizes = QUICK_SIZES.filter((size) => quickSizes[size].checked);
      for (const size of checkedSizes) {
        const state = quickSizes[size];
        await createProductVariant(created.id, {
          sku: state.sku || `${sku}-${size}${sizeUnit}`,
          size_value: size,
          size_unit: sizeUnit,
          price_override: state.price ? Math.round(Number(state.price) * 100) : null,
          initial_stock: Number(state.stock) || 0,
        });
      }

      // The product itself is safely created from this point on — nothing
      // below (an image upload failing) can lose it or duplicate it.
      setCreatedProductId(created.id);
      setSaving(false);

      if (stagedImages.length === 0) {
        toast.show({ message: t("productCreated") });
        router.push(`/adminportal/products/${created.id}/edit`);
        return;
      }

      const results = await Promise.all(stagedImages.map((staged) => uploadStagedImage(created.id, staged)));
      if (results.every(Boolean)) {
        toast.show({ message: t("productCreated") });
        router.push(`/adminportal/products/${created.id}/edit`);
      }
      // If any image failed, stay put: the images section below shows each
      // status with a retry button, plus an always-visible link onward so
      // the admin is never stuck.
    } catch (err) {
      setError(adminErrorMessage(err, t));
      setFieldErrors(adminFieldErrors(err, t));
      setSaving(false);
    }
  }

  const stagedDropzoneImages: DropzoneImage[] = stagedImages.map((s) => ({
    id: s.localId,
    url: s.previewUrl,
    status: "ready",
  }));

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
      {!productId && !createdProductId && (
        <Section title={t("images")}>
          <ImageDropzone
            images={stagedDropzoneImages}
            onFilesSelected={handleImagesSelected}
            onDelete={handleDeleteStagedImage}
            onReorder={handleReorderStagedImages}
          />
        </Section>
      )}

      {createdProductId && stagedImages.length > 0 && (
        <Section title={t("images")}>
          <div className="flex flex-wrap gap-4">
            {stagedImages.map((staged) => {
              const imgStatus = imageUploadStatuses[staged.localId] ?? "uploading";
              return (
                <div key={staged.localId} className="w-28 space-y-1">
                  <div className="relative h-24 w-24 overflow-hidden rounded-lg border border-primary/10">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={staged.previewUrl} alt="" className="h-full w-full object-cover" />
                    {imgStatus === "uploading" && (
                      <div className="absolute inset-0 flex items-center justify-center bg-ink/40">
                        <span className="h-5 w-5 animate-spin rounded-full border-2 border-background border-t-transparent" />
                      </div>
                    )}
                    {imgStatus === "failed" && (
                      <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-error/80 p-1 text-center">
                        <span className="text-caption font-medium text-background">{t("imageFailed")}</span>
                        <button
                          type="button"
                          onClick={() => handleRetryStagedImage(staged.localId)}
                          className="rounded-full bg-background px-2 py-0.5 text-caption font-semibold text-error"
                        >
                          {t("retryUpload")}
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
          <Link
            href={`/adminportal/products/${createdProductId}/edit`}
            className="inline-block text-small font-semibold text-primary hover:underline"
          >
            {t("continueToProduct")} →
          </Link>
        </Section>
      )}

      <Section title={t("sectionBasicInfo")}>
        <Field label={t("category")} error={fieldErrors.category_id}>
          <select
            value={categoryId}
            onChange={(event) => setCategoryId(event.target.value)}
            required
            disabled={categoriesLoading}
            className={`${inputClass} ${fieldErrors.category_id ? inputErrorClass : ""}`}
          >
            <option value="" disabled>
              {categoriesLoading ? t("loading") : "—"}
            </option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name[locale]}
              </option>
            ))}
          </select>
        </Field>

        <Field label={t("nameAr")} error={fieldErrors.name_ar}>
          <input
            value={nameAr}
            onChange={(event) => setNameAr(event.target.value)}
            required
            dir="rtl"
            className={`${inputClass} ${fieldErrors.name_ar ? inputErrorClass : ""}`}
          />
        </Field>
        <Field label={t("nameFr")} error={fieldErrors.name_fr}>
          <input
            value={nameFr}
            onChange={(event) => setNameFr(event.target.value)}
            required
            className={`${inputClass} ${fieldErrors.name_fr ? inputErrorClass : ""}`}
          />
        </Field>
        <Field label={t("nameEn")} error={fieldErrors.name_en}>
          <input
            value={nameEn}
            onChange={(event) => setNameEn(event.target.value)}
            required
            className={`${inputClass} ${fieldErrors.name_en ? inputErrorClass : ""}`}
          />
        </Field>

        <Field label={t("descriptionAr")}>
          <textarea
            value={descAr}
            onChange={(event) => setDescAr(event.target.value)}
            dir="rtl"
            rows={3}
            className={inputClass}
          />
        </Field>
        <Field label={t("descriptionFr")}>
          <textarea value={descFr} onChange={(event) => setDescFr(event.target.value)} rows={3} className={inputClass} />
        </Field>
        <Field label={t("descriptionEn")}>
          <textarea value={descEn} onChange={(event) => setDescEn(event.target.value)} rows={3} className={inputClass} />
        </Field>

        <Field label={t("seoTitle")}>
          <input value={seoTitle} onChange={(event) => setSeoTitle(event.target.value)} className={inputClass} />
        </Field>
        <Field label={t("seoDescription")}>
          <textarea
            value={seoDescription}
            onChange={(event) => setSeoDescription(event.target.value)}
            rows={2}
            className={inputClass}
          />
        </Field>
      </Section>

      <Section title={t("sectionPricing")}>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label={t("basePrice")} error={fieldErrors.base_price}>
            <PriceInput
              min={1}
              value={basePrice}
              onChange={setBasePrice}
              required
              className={`${inputClass} ${fieldErrors.base_price ? inputErrorClass : ""}`}
            />
          </Field>
          <Field label={t("compareAtPrice")} error={fieldErrors.compare_at_price}>
            <PriceInput
              min={0}
              value={comparePrice}
              onChange={setComparePrice}
              className={`${inputClass} ${fieldErrors.compare_at_price ? inputErrorClass : ""}`}
            />
          </Field>
        </div>
      </Section>

      {!productId && (
        <Section title={t("sectionInventory")}>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
            <Field label={t("sku")} error={fieldErrors.sku}>
              <input
                value={sku}
                onChange={(event) => setSku(event.target.value)}
                required
                className={`${inputClass} ${fieldErrors.sku ? inputErrorClass : ""}`}
              />
            </Field>
            <Field label={t("sizeValue")} error={fieldErrors.size_value}>
              <input
                type="number"
                min={0}
                step="0.01"
                value={sizeValue}
                onChange={(event) => setSizeValue(event.target.value)}
                className={inputClass}
              />
            </Field>
            <Field label={t("sizeUnit")}>
              <select value={sizeUnit} onChange={(event) => setSizeUnit(event.target.value)} className={inputClass}>
                <option value="ml">{t("unitMl")}</option>
                <option value="g">{t("unitG")}</option>
                <option value="oz">{t("unitOz")}</option>
              </select>
            </Field>
            <Field label={t("initialStock")}>
              <input
                type="number"
                min={0}
                value={initialStock}
                onChange={(event) => setInitialStock(event.target.value)}
                required
                className={inputClass}
              />
            </Field>
            <Field label={t("lowStockThreshold")}>
              <input
                type="number"
                min={1}
                value={lowStockThreshold}
                onChange={(event) => setLowStockThreshold(event.target.value)}
                className={inputClass}
              />
            </Field>
          </div>

          <div className="rounded-xl border border-primary/10 bg-primary/5 p-4">
            <p className="mb-1 text-small font-semibold text-ink">{t("quickAddSizes")}</p>
            <p className="mb-3 text-caption text-ink/60">{t("quickAddSizesHint")}</p>
            <div className="space-y-3">
              {QUICK_SIZES.map((size) => (
                <div key={size} className="flex flex-wrap items-center gap-3">
                  <label className="flex w-24 shrink-0 items-center gap-2 text-small text-ink/80">
                    <input
                      type="checkbox"
                      checked={quickSizes[size].checked}
                      onChange={(event) => updateQuickSize(size, { checked: event.target.checked })}
                    />
                    {size} {t(`unit${sizeUnit[0].toUpperCase()}${sizeUnit.slice(1)}`)}
                  </label>
                  {quickSizes[size].checked && (
                    <>
                      <PriceInput
                        min={0}
                        placeholder={t("priceOverride")}
                        value={quickSizes[size].price}
                        onChange={(value) => updateQuickSize(size, { price: value })}
                        className={`${smallInputClass} w-32`}
                      />
                      <input
                        type="number"
                        min={0}
                        placeholder={t("initialStock")}
                        value={quickSizes[size].stock}
                        onChange={(event) => updateQuickSize(size, { stock: event.target.value })}
                        className={`${smallInputClass} w-28`}
                      />
                      <input
                        type="text"
                        placeholder={t("variantSku")}
                        value={quickSizes[size].sku}
                        onChange={(event) => updateQuickSize(size, { sku: event.target.value })}
                        className={`${smallInputClass} w-40`}
                      />
                    </>
                  )}
                </div>
              ))}
            </div>
          </div>
        </Section>
      )}

      <Section title={t("status")}>
        <Field label={t("status")}>
          <select
            value={status}
            onChange={(event) => setStatus(event.target.value as AdminProduct["status"])}
            className={inputClass}
          >
            <option value="draft">{t("statusDraft")}</option>
            <option value="active">{t("active")}</option>
            <option value="archived">{t("archived")}</option>
          </select>
        </Field>

        <div>
          <label className="flex items-center gap-2 text-small text-ink/80">
            <input type="checkbox" checked={featured} onChange={(event) => setFeatured(event.target.checked)} />
            {t("featured")}
          </label>
          <p className="mt-1 text-caption text-ink/50">{t("featuredHint")}</p>
        </div>
      </Section>

      {error && <p className="text-small text-error">{error}</p>}

      <div className="flex items-center gap-3">
        <button
          type="submit"
          disabled={saving}
          className="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {saving && <span className="h-4 w-4 animate-spin rounded-full border-2 border-background border-t-transparent" />}
          {saving ? t("savingProduct") : t("save")}
        </button>
        <Link href="/adminportal/products" className="text-small text-ink/60 hover:text-ink">
          {t("cancel")}
        </Link>
      </div>
    </form>
  );
}

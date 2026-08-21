"use client";

import { useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import {
  ApiError,
  createProductVariant,
  deleteProductVariant,
  updateProductVariant,
  type AdminVariant,
  type VariantPayload,
} from "@/lib/api";
import { StockAdjust } from "@/components/admin/StockAdjust";
import { PriceInput } from "@/components/admin/PriceInput";
import { formatPrice } from "@/lib/currency";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

const SIZE_UNITS = ["ml", "g", "oz"] as const;

function errorMessage(error: unknown, t: (key: string) => string): string {
  if (error instanceof ApiError) {
    if (error.code === "last_variant") return t("cannotDeleteLastVariant");
    if (error.code === "variant_has_orders") return t("variantHasOrders");
    if (error.code === "duplicate_variant_size") return t("duplicateVariantSize");
  }
  return t("errorGeneric");
}

function formatSize(variant: AdminVariant): string {
  if (variant.size_value != null && variant.size_unit) {
    return `${variant.size_value} ${variant.size_unit}`;
  }
  return [variant.color, variant.size].filter(Boolean).join(" / ") || "—";
}

type VariantFormState = {
  sku: string;
  color: string;
  sizeValue: string;
  sizeUnit: string;
  priceOverride: string;
  compareAtPrice: string;
  weight: string;
  lowStockThreshold: string;
  sortOrder: string;
  isActive: boolean;
};

function emptyFormState(nextSortOrder: number): VariantFormState {
  return {
    sku: "",
    color: "",
    sizeValue: "",
    sizeUnit: "ml",
    priceOverride: "",
    compareAtPrice: "",
    weight: "",
    lowStockThreshold: "5",
    sortOrder: String(nextSortOrder),
    isActive: true,
  };
}

function formStateFromVariant(variant: AdminVariant): VariantFormState {
  return {
    sku: variant.sku,
    color: variant.color ?? "",
    sizeValue: variant.size_value != null ? String(variant.size_value) : "",
    sizeUnit: variant.size_unit ?? "ml",
    // Displayed/entered in plain DZD, not the centimes the API stores/
    // expects — converted back in buildPayload() below.
    priceOverride: variant.price_override != null ? String(variant.price_override / 100) : "",
    compareAtPrice: variant.compare_at_price != null ? String(variant.compare_at_price / 100) : "",
    weight: variant.weight != null ? String(variant.weight) : "",
    lowStockThreshold: String(variant.low_stock_threshold),
    sortOrder: String(variant.sort_order),
    isActive: variant.is_active,
  };
}

function VariantFields({ state, onChange }: { state: VariantFormState; onChange: (next: VariantFormState) => void }) {
  const t = useTranslations("Admin");

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("variantSku")}</span>
        <input value={state.sku} onChange={(e) => onChange({ ...state, sku: e.target.value })} required className={inputClass} />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("sizeValue")}</span>
        <input
          type="number"
          min={0}
          step="0.01"
          value={state.sizeValue}
          onChange={(e) => onChange({ ...state, sizeValue: e.target.value })}
          className={inputClass}
        />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("sizeUnit")}</span>
        <select value={state.sizeUnit} onChange={(e) => onChange({ ...state, sizeUnit: e.target.value })} className={inputClass}>
          {SIZE_UNITS.map((unit) => (
            <option key={unit} value={unit}>
              {t(`unit${unit[0].toUpperCase()}${unit.slice(1)}`)}
            </option>
          ))}
        </select>
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("color")}</span>
        <input value={state.color} onChange={(e) => onChange({ ...state, color: e.target.value })} className={inputClass} />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("priceOverride")}</span>
        <PriceInput
          min={0}
          value={state.priceOverride}
          onChange={(value) => onChange({ ...state, priceOverride: value })}
          className={inputClass}
        />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("compareAtPrice")}</span>
        <PriceInput
          min={0}
          value={state.compareAtPrice}
          onChange={(value) => onChange({ ...state, compareAtPrice: value })}
          className={inputClass}
        />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("lowStockThreshold")}</span>
        <input
          type="number"
          min={1}
          value={state.lowStockThreshold}
          onChange={(e) => onChange({ ...state, lowStockThreshold: e.target.value })}
          className={inputClass}
        />
      </label>
      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("sortOrder")}</span>
        <input
          type="number"
          value={state.sortOrder}
          onChange={(e) => onChange({ ...state, sortOrder: e.target.value })}
          className={inputClass}
        />
      </label>

      <label className="block">
        <span className="mb-1 block text-caption text-ink/60">{t("weight")}</span>
        <input
          type="number"
          min={0}
          step="0.001"
          value={state.weight}
          onChange={(e) => onChange({ ...state, weight: e.target.value })}
          className={inputClass}
        />
      </label>
      <label className="flex items-center gap-2 self-end text-small text-ink/80">
        <input type="checkbox" checked={state.isActive} onChange={(e) => onChange({ ...state, isActive: e.target.checked })} />
        {t("active")}
      </label>
    </div>
  );
}

function buildPayload(state: VariantFormState): Omit<VariantPayload, "initial_stock"> {
  return {
    sku: state.sku,
    color: state.color || null,
    price_override: state.priceOverride ? Math.round(Number(state.priceOverride) * 100) : null,
    compare_at_price: state.compareAtPrice ? Math.round(Number(state.compareAtPrice) * 100) : null,
    size_value: state.sizeValue ? Number(state.sizeValue) : null,
    size_unit: state.sizeValue ? state.sizeUnit : null,
    sort_order: Number(state.sortOrder) || 0,
    is_active: state.isActive,
    weight: state.weight ? Number(state.weight) : null,
    low_stock_threshold: Number(state.lowStockThreshold),
  };
}

function VariantEditForm({
  variant,
  onSave,
  onCancel,
}: {
  variant: AdminVariant;
  onSave: (payload: Partial<Omit<VariantPayload, "initial_stock">>) => Promise<void>;
  onCancel: () => void;
}) {
  const t = useTranslations("Admin");
  const [state, setState] = useState(formStateFromVariant(variant));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      await onSave(buildPayload(state));
    } catch (err) {
      setError(errorMessage(err, t));
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3 rounded-lg bg-primary/5 p-4">
      <VariantFields state={state} onChange={setState} />
      {error && <p className="text-caption text-error">{error}</p>}
      <div className="flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-4 py-2 text-caption font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("save")}
        </button>
        <button type="button" onClick={onCancel} className="text-caption text-ink/60 hover:text-ink">
          {t("cancel")}
        </button>
      </div>
    </form>
  );
}

function VariantCreateForm({
  nextSortOrder,
  onCreate,
  onCancel,
}: {
  nextSortOrder: number;
  onCreate: (payload: VariantPayload) => Promise<void>;
  onCancel: () => void;
}) {
  const t = useTranslations("Admin");
  const [state, setState] = useState(emptyFormState(nextSortOrder));
  const [initialStock, setInitialStock] = useState("0");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      await onCreate({ ...buildPayload(state), initial_stock: Number(initialStock) });
    } catch (err) {
      setError(errorMessage(err, t));
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3 rounded-2xl bg-white p-4 shadow-soft">
      <VariantFields state={state} onChange={setState} />
      <label className="block max-w-xs">
        <span className="mb-1 block text-caption text-ink/60">{t("initialStock")}</span>
        <input
          type="number"
          min={0}
          value={initialStock}
          onChange={(e) => setInitialStock(e.target.value)}
          required
          className={inputClass}
        />
      </label>

      {error && <p className="text-caption text-error">{error}</p>}

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={saving}
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
        >
          {t("addVariant")}
        </button>
        <button type="button" onClick={onCancel} className="text-small text-ink/60 hover:text-ink">
          {t("cancel")}
        </button>
      </div>
    </form>
  );
}

export function VariantManager({ productId, initial }: { productId: string; initial: AdminVariant[] }) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;
  const [variants, setVariants] = useState<AdminVariant[]>(initial);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [adding, setAdding] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleCreate(payload: VariantPayload) {
    const product = await createProductVariant(productId, payload);
    setVariants(product.variants);
    setAdding(false);
  }

  async function handleUpdate(variantId: string, payload: Partial<Omit<VariantPayload, "initial_stock">>) {
    const product = await updateProductVariant(productId, variantId, payload);
    setVariants(product.variants);
    setEditingId(null);
  }

  async function handleDelete(variantId: string) {
    if (!window.confirm(t("confirmDeleteVariant"))) return;
    setError(null);
    try {
      const product = await deleteProductVariant(productId, variantId);
      setVariants(product.variants);
    } catch (err) {
      setError(errorMessage(err, t));
    }
  }

  const nextSortOrder = variants.length;

  return (
    <div className="space-y-4">
      {error && <p className="text-small text-error">{error}</p>}

      <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
        <table className="w-full text-start text-small">
          <thead>
            <tr className="border-b border-primary/10 text-ink/60">
              <th className="px-4 py-3 text-start font-medium">{t("size")}</th>
              <th className="px-4 py-3 text-start font-medium">{t("price")}</th>
              <th className="px-4 py-3 text-start font-medium">{t("compareAtPrice")}</th>
              <th className="px-4 py-3 text-start font-medium">{t("stock")}</th>
              <th className="px-4 py-3 text-start font-medium">SKU</th>
              <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
              <th className="px-4 py-3 text-start font-medium">{t("sortOrder")}</th>
              <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
            </tr>
          </thead>
          <tbody>
            {variants.map((variant) =>
              editingId === variant.id ? (
                <tr key={variant.id}>
                  <td colSpan={8} className="space-y-3 p-4">
                    <VariantEditForm
                      variant={variant}
                      onSave={(payload) => handleUpdate(variant.id, payload)}
                      onCancel={() => setEditingId(null)}
                    />
                    <StockAdjust
                      variantId={variant.id}
                      stockQuantity={variant.stock_quantity}
                      reservedQuantity={variant.reserved_quantity}
                      availableQuantity={variant.available_quantity}
                    />
                  </td>
                </tr>
              ) : (
                <tr key={variant.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3 text-ink">{formatSize(variant)}</td>
                  <td className="px-4 py-3 text-ink/80">
                    {variant.price_override != null ? formatPrice(variant.price_override, locale) : "—"}
                  </td>
                  <td className="px-4 py-3 text-ink/60">
                    {variant.compare_at_price != null ? formatPrice(variant.compare_at_price, locale) : "—"}
                  </td>
                  <td className="px-4 py-3 text-ink/70">
                    {variant.available_quantity} / {variant.stock_quantity}
                  </td>
                  <td className="px-4 py-3 text-ink/70">{variant.sku}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-1 text-caption font-medium ${
                        variant.is_active ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {variant.is_active ? t("active") : t("inactive")}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-ink/60">{variant.sort_order}</td>
                  <td className="px-4 py-3">
                    <div className="flex gap-3">
                      <button type="button" onClick={() => setEditingId(variant.id)} className="text-primary hover:underline">
                        {t("edit")}
                      </button>
                      {variants.length > 1 && (
                        <button type="button" onClick={() => handleDelete(variant.id)} className="text-error hover:underline">
                          {t("delete")}
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ),
            )}
          </tbody>
        </table>
      </div>

      {adding ? (
        <VariantCreateForm nextSortOrder={nextSortOrder} onCreate={handleCreate} onCancel={() => setAdding(false)} />
      ) : (
        <button
          type="button"
          onClick={() => setAdding(true)}
          className="rounded-full border border-primary/20 px-5 py-2.5 text-small font-semibold text-primary hover:bg-primary/5"
        >
          {t("addVariant")}
        </button>
      )}
    </div>
  );
}

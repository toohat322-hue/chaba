import { ApiError } from "./api";

// Maps a validation-failed field name to the specific i18n key that best
// explains it to an admin — mirrors VariantManager.tsx's errorMessage()
// convention (a small curated map from error identity to a localized
// string) but keyed by field name since the backend's field_errors carry
// English Laravel messages the trilingual admin UI can't render as-is.
const FIELD_ERROR_KEYS: Record<string, string> = {
  sku: "errorSkuTaken",
  slug: "errorSlugTaken",
  category_id: "errorCategoryInvalid",
  base_price: "errorPriceInvalid",
  compare_at_price: "errorPriceInvalid",
  name_ar: "errorNameRequired",
  name_fr: "errorNameRequired",
  name_en: "errorNameRequired",
  size_value: "errorSizeInvalid",
  size_unit: "errorSizeInvalid",
  image: "errorImageInvalid",
};

export function adminErrorMessage(error: unknown, t: (key: string) => string): string {
  if (error instanceof ApiError) {
    const field = error.fieldErrors && Object.keys(error.fieldErrors)[0];
    if (field && FIELD_ERROR_KEYS[field]) return t(FIELD_ERROR_KEYS[field]);
    if (error.status >= 500) return t("errorServer");
    if (error.fieldErrors) return t("errorValidationGeneric");
    return t("errorGeneric");
  }
  // fetch() itself rejects (offline, DNS failure, CORS) with a TypeError —
  // the request never reached the server, so this is a distinct case from
  // any ApiError above (which means the server *did* respond).
  if (error instanceof TypeError) return t("errorNetwork");
  return t("errorGeneric");
}

// Per-field messages for inline display next to the offending input.
export function adminFieldErrors(error: unknown, t: (key: string) => string): Record<string, string> {
  if (!(error instanceof ApiError) || !error.fieldErrors) return {};
  return Object.fromEntries(
    Object.keys(error.fieldErrors)
      .filter((field) => FIELD_ERROR_KEYS[field])
      .map((field) => [field, t(FIELD_ERROR_KEYS[field])]),
  );
}

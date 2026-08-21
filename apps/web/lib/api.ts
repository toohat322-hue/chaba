/**
 * Thin client for the CHABA API (apps/api). Unwraps the {success,data,error}
 * envelope (PRD §13) so callers just get typed data or a thrown error —
 * never a fabricated fallback value on failure.
 */

import { getSessionToken } from "./cart-session";
import { getAdminTokens, setAdminTokens, clearAdminTokens } from "./admin-session";
import { getCustomerTokens, setCustomerTokens, clearCustomerTokens } from "./customer-session";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

type ApiEnvelope<T> =
  | { success: true; data: T; error: null }
  | { success: false; data: null; error: { code: string; message: string; field_errors?: Record<string, string[]> } };

export class ApiError extends Error {
  constructor(
    public readonly code: string,
    message: string,
    public readonly status: number,
    public readonly fieldErrors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    // Catalog data (stock, prices, featured flags) changes independently of
    // deploys — this must render fresh per request, never get baked into a
    // static build artifact.
    cache: "no-store",
    ...init,
    headers: { Accept: "application/json", ...init?.headers },
  });

  // response.json() is never guarded by response.ok here on purpose — every
  // real API response (success or error) is JSON per the envelope contract.
  // But an infra-level failure (proxy/gateway timeout, an HTML error page
  // from a reverse proxy) isn't a real API response, so both possible
  // failures — a body that isn't JSON at all, and JSON that doesn't match
  // the envelope shape — are turned into a normal ApiError instead of an
  // uncaught SyntaxError/TypeError that every caller pattern-matching on
  // `error instanceof ApiError` would otherwise miss.
  let envelope: ApiEnvelope<T> | null = null;
  try {
    envelope = await response.json();
  } catch {
    envelope = null;
  }

  if (!envelope || typeof envelope.success !== "boolean") {
    throw new ApiError("unexpected_response", "Unexpected response from the server.", response.status);
  }

  if (!envelope.success) {
    throw new ApiError(envelope.error.code, envelope.error.message, response.status, envelope.error.field_errors);
  }

  return envelope.data;
}

export type LocalizedText = { ar: string; fr: string; en: string };

export type ProductListItem = {
  id: string;
  slug: string;
  name: LocalizedText;
  price: number;
  compare_at_price: number | null;
  in_stock: boolean;
  featured: boolean;
  avg_rating: number;
  review_count: number;
  image_url: string | null;
  image_alt: string | null;
  updated_at: string;
};

export function getFeaturedProducts(): Promise<ProductListItem[]> {
  return apiFetch<ProductListItem[]>("/featured-products");
}

export type HeroSlide = {
  id: string;
  product: { slug: string; name: LocalizedText };
  image_url: string | null;
  mobile_image_url: string | null;
  title: LocalizedText;
  subtitle: NullableLocalizedText;
  cta_label: LocalizedText;
};

export function getHeroSlides(): Promise<HeroSlide[]> {
  return apiFetch<HeroSlide[]>("/hero-slides");
}

export type Category = {
  id: string;
  slug: string;
  name: LocalizedText;
  image_url: string | null;
  sort_order: number;
  seo_title: string | null;
  seo_description: string | null;
  updated_at: string;
  children: Category[];
};

export function getCategory(slug: string): Promise<Category> {
  return apiFetch<Category>(`/categories/${encodeURIComponent(slug)}`);
}

export function getCategories(): Promise<Category[]> {
  return apiFetch<Category[]>("/categories");
}

export type ProductVariantDetail = {
  id: string;
  sku: string;
  color: string | null;
  size: string | null;
  size_value: number | null;
  size_unit: string | null;
  price: number;
  compare_at_price: number | null;
  sort_order: number;
  available_quantity: number;
  low_stock: boolean;
};

export type ProductDetail = {
  id: string;
  slug: string;
  name: LocalizedText;
  description: LocalizedText;
  base_price: number;
  compare_at_price: number | null;
  status: string;
  featured: boolean;
  seo_title: string | null;
  seo_description: string | null;
  updated_at: string;
  avg_rating: number;
  review_count: number;
  category?: { slug: string; name: LocalizedText };
  images: { url: string; alt_text: string | null; variant_id: string | null }[];
  variants: ProductVariantDetail[];
  related: ProductListItem[];
};

export function getProduct(slug: string): Promise<ProductDetail> {
  return apiFetch<ProductDetail>(`/products/${encodeURIComponent(slug)}`);
}

export type ProductListMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type ProductListParams = {
  category?: string;
  sort?: "price_asc" | "price_desc" | "rating" | "newest";
  page?: number;
};

export function getProducts(
  params: ProductListParams = {},
): Promise<{ items: ProductListItem[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.category) search.set("category", params.category);
  if (params.sort) search.set("sort", params.sort);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return apiFetch<{ items: ProductListItem[]; meta: ProductListMeta }>(
    `/products${query ? `?${query}` : ""}`,
  );
}

export type SearchResult = {
  items: ProductListItem[];
  query: string;
  fallback?: boolean;
  no_results?: boolean;
  suggestions?: ProductListItem[];
  meta?: ProductListMeta;
};

export type SearchParams = {
  sort?: "price_asc" | "price_desc" | "rating" | "newest";
  page?: number;
};

export function search(q: string, params: SearchParams = {}): Promise<SearchResult> {
  const search = new URLSearchParams({ q });
  if (params.sort) search.set("sort", params.sort);
  if (params.page) search.set("page", String(params.page));

  return apiFetch<SearchResult>(`/search?${search.toString()}`);
}

/**
 * Resolves a renamed product/category slug to its current one (see
 * SlugRedirect on the API side), so a page whose slug no longer exists can
 * 301 to the current URL instead of hard-404ing. Returns null rather than
 * throwing when there's no redirect — callers treat "no redirect" as a
 * normal, expected outcome, not an error.
 */
export async function resolveSlugRedirect(type: "product" | "category", slug: string): Promise<string | null> {
  try {
    const { slug: currentSlug } = await apiFetch<{ slug: string }>(
      `/redirects/resolve?type=${type}&slug=${encodeURIComponent(slug)}`,
    );
    return currentSlug;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }
    throw error;
  }
}

// --- Cart (client-only: needs the guest session token from localStorage) ---

export type CartItemDetail = {
  id: string;
  variant_id: string;
  product_slug: string;
  product_name: LocalizedText;
  sku: string;
  color: string | null;
  size: string | null;
  size_value: number | null;
  size_unit: string | null;
  quantity: number;
  price_snapshot: number;
  current_price: number;
  price_changed: boolean;
  image_url: string | null;
  available_quantity: number;
  exceeds_stock: boolean;
};

export type CartData = {
  id: string;
  items: CartItemDetail[];
  subtotal: number;
  discount_total: number;
  coupon: { code: string; type: string } | null;
  item_count: number;
};

// Prefers the logged-in customer's identity when one exists, so their cart
// and any order placed through checkout are tied to their account (and thus
// visible in My Orders) — falls back to the guest session otherwise. Cart
// items added *before* login are not merged into the account (a distinct,
// deferred feature); this only affects which identity new operations use.
function cartFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const { access } = getCustomerTokens();
  const identityHeader: Record<string, string> = access
    ? { Authorization: `Bearer ${access}` }
    : { "X-Guest-Session": getSessionToken() };

  return apiFetch<T>(path, {
    ...init,
    headers: { ...identityHeader, ...init?.headers },
  });
}

export function getCart(): Promise<CartData> {
  return cartFetch<CartData>("/cart");
}

export function addCartItem(variantId: string, quantity: number): Promise<CartData> {
  return cartFetch<CartData>("/cart/items", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ variant_id: variantId, quantity }),
  });
}

export function updateCartItem(itemId: string, quantity: number): Promise<CartData> {
  return cartFetch<CartData>(`/cart/items/${encodeURIComponent(itemId)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ quantity }),
  });
}

export function removeCartItem(itemId: string): Promise<CartData> {
  return cartFetch<CartData>(`/cart/items/${encodeURIComponent(itemId)}`, { method: "DELETE" });
}

export function applyCoupon(code: string): Promise<CartData> {
  return cartFetch<CartData>("/cart/coupon", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ code }),
  });
}

export function removeCoupon(): Promise<CartData> {
  return cartFetch<CartData>("/cart/coupon", { method: "DELETE" });
}

// --- Wishlist (client-only: same guest/customer identity as Cart) ---

export type WishlistItem = {
  id: string;
  product_id: string;
  product_slug: string;
  variant_id: string;
  name: LocalizedText;
  price: number;
  compare_at_price: number | null;
  in_stock: boolean;
  image_url: string | null;
  created_at: string;
};

// Same guest-vs-customer identity resolution as cartFetch, and the same
// deferred gap: items added before login aren't merged into the account.
function wishlistFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const { access } = getCustomerTokens();
  const identityHeader: Record<string, string> = access
    ? { Authorization: `Bearer ${access}` }
    : { "X-Guest-Session": getSessionToken() };

  return apiFetch<T>(path, {
    ...init,
    headers: { ...identityHeader, ...init?.headers },
  });
}

export function getWishlist(): Promise<WishlistItem[]> {
  return wishlistFetch<WishlistItem[]>("/wishlist");
}

export function addToWishlist(productId: string): Promise<WishlistItem> {
  return wishlistFetch<WishlistItem>("/wishlist", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ product_id: productId }),
  });
}

export function removeFromWishlist(productId: string): Promise<{ message: string }> {
  return wishlistFetch<{ message: string }>(`/wishlist/${encodeURIComponent(productId)}`, { method: "DELETE" });
}

// --- Admin ---

export type AdminUser = {
  id: string;
  full_name: string;
  phone: string;
  email: string | null;
  role: string | null;
  two_factor_enabled?: boolean;
};

export function getAdminMe(): Promise<AdminUser> {
  return adminFetch("/users/me");
}

type TokenPair = { access_token: string; refresh_token: string };

export type AdminLoginResult =
  | ({ requires_two_factor: false; user: AdminUser } & TokenPair)
  | { requires_two_factor: true; two_factor_token: string };

export async function adminLogin(login: string, password: string): Promise<AdminLoginResult> {
  const data = await apiFetch<
    ({ user: AdminUser } & TokenPair) | { requires_two_factor: true; two_factor_token: string }
  >("/auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ login, password }),
  });

  if ("requires_two_factor" in data) {
    return data;
  }

  if (!data.user.role) {
    throw new ApiError("forbidden", "This account has no admin access.", 403);
  }

  setAdminTokens(data.access_token, data.refresh_token);

  return { requires_two_factor: false, ...data };
}

export async function adminVerifyTwoFactor(twoFactorToken: string, code: string): Promise<{ user: AdminUser }> {
  const data = await apiFetch<{ user: AdminUser } & TokenPair>("/auth/2fa/verify", {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${twoFactorToken}` },
    body: JSON.stringify({ code }),
  });

  if (!data.user.role) {
    throw new ApiError("forbidden", "This account has no admin access.", 403);
  }

  setAdminTokens(data.access_token, data.refresh_token);

  return data;
}

export type TwoFactorSetup = { secret: string; otpauth_url: string };

export function startTwoFactorSetup(): Promise<TwoFactorSetup> {
  return adminFetch("/users/me/2fa", { method: "POST" });
}

export function confirmTwoFactorSetup(code: string): Promise<{ recovery_codes: string[] }> {
  return adminFetch("/users/me/2fa/confirm", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ code }),
  });
}

export function disableTwoFactor(password: string): Promise<{ message: string }> {
  return adminFetch("/users/me/2fa", {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ password }),
  });
}

export async function adminLogout(): Promise<void> {
  const { access } = getAdminTokens();

  if (access) {
    await apiFetch("/auth/logout", {
      method: "POST",
      headers: { Authorization: `Bearer ${access}` },
    }).catch(() => undefined);
  }

  clearAdminTokens();
}

async function refreshAdminSession(): Promise<string | null> {
  const { refresh } = getAdminTokens();
  if (!refresh) {
    return null;
  }

  try {
    const data = await apiFetch<TokenPair>("/auth/refresh", {
      method: "POST",
      headers: { Authorization: `Bearer ${refresh}` },
    });
    setAdminTokens(data.access_token, data.refresh_token);

    return data.access_token;
  } catch {
    clearAdminTokens();

    return null;
  }
}

// Two admin requests that both 401 around the same time (e.g. the
// dashboard's two concurrent fetches on mount) would otherwise each call
// refreshAdminSession() independently — both read the same still-stale
// refresh token, both POST /auth/refresh, and since refresh tokens are
// single-use/rotating, whichever request's response arrives second gets
// "invalid token" on an already-consumed token and wipes out the tokens
// the other one just set, silently logging the admin out mid-session
// despite the refresh having actually succeeded. Sharing one in-flight
// promise makes every concurrent 401 await the same refresh instead.
let adminRefreshPromise: Promise<string | null> | null = null;

function refreshAdminSessionOnce(): Promise<string | null> {
  if (!adminRefreshPromise) {
    adminRefreshPromise = refreshAdminSession().finally(() => {
      adminRefreshPromise = null;
    });
  }

  return adminRefreshPromise;
}

async function adminFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const { access } = getAdminTokens();

  try {
    return await apiFetch<T>(path, {
      ...init,
      headers: { Authorization: `Bearer ${access ?? ""}`, ...init?.headers },
    });
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      const refreshedAccess = await refreshAdminSessionOnce();
      if (refreshedAccess) {
        return apiFetch<T>(path, {
          ...init,
          headers: { Authorization: `Bearer ${refreshedAccess}`, ...init?.headers },
        });
      }
    }
    throw error;
  }
}

export function getAdminDashboard(): Promise<{
  total_products: number;
  total_categories: number;
  total_customers: number;
  low_stock_count: number;
  orders_available: boolean;
  total_orders: number | null;
  total_sales: number | null;
  pending_orders: number | null;
}> {
  return adminFetch("/admin/dashboard");
}

/**
 * CSV export endpoints return a raw file, not the {success,data,error}
 * envelope apiFetch/adminFetch expect — a separate client-only fetch that
 * triggers a browser download instead of parsing JSON.
 */
export async function downloadAdminCsv(path: string, filename: string): Promise<void> {
  const { access } = getAdminTokens();

  let response = await fetch(`${API_URL}${path}`, {
    headers: { Authorization: `Bearer ${access ?? ""}` },
  });

  // Unlike adminFetch, this never went through apiFetch, so it never
  // self-healed an expired token — every other admin action would
  // transparently refresh and succeed, but export just failed outright.
  if (response.status === 401) {
    const refreshedAccess = await refreshAdminSessionOnce();
    if (refreshedAccess) {
      response = await fetch(`${API_URL}${path}`, {
        headers: { Authorization: `Bearer ${refreshedAccess}` },
      });
    }
  }

  if (!response.ok) {
    throw new ApiError("export_failed", "Unable to export this file.", response.status);
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export type AdminDashboardAnalytics = {
  sales_last_30_days: { date: string; total: number }[];
  top_products: { name: string; quantity_sold: number }[];
  orders_by_status: { status: string; count: number }[];
};

export function getAdminDashboardAnalytics(): Promise<AdminDashboardAnalytics> {
  return adminFetch("/admin/dashboard/analytics");
}

export type AdminSettings = {
  store_name: string;
  support_email: string | null;
  support_phone: string | null;
  store_address: string | null;
  tax_rate_bps: number;
  payment_providers: { cod: boolean; cib: boolean; edahabia: boolean };
  about: { title: LocalizedText; description: LocalizedText };
  whatsapp: { number: string | null; active: boolean; orders_enabled: boolean; message: LocalizedText };
};

export function getAdminSettings(): Promise<AdminSettings> {
  return adminFetch("/admin/settings");
}

export function updateAdminSettings(payload: {
  store_name: string;
  support_email?: string | null;
  support_phone?: string | null;
  store_address?: string | null;
  tax_rate_bps: number;
  about_title_ar?: string;
  about_title_fr?: string;
  about_title_en?: string;
  about_description_ar?: string;
  about_description_fr?: string;
  about_description_en?: string;
  whatsapp_number?: string | null;
  whatsapp_message_ar?: string;
  whatsapp_message_fr?: string;
  whatsapp_message_en?: string;
  whatsapp_active?: boolean;
  whatsapp_orders_enabled?: boolean;
}): Promise<AdminSettings> {
  return adminFetch("/admin/settings", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export type AdminProduct = {
  id: string;
  slug: string;
  category_id: string | null;
  category?: { id: string; name: LocalizedText };
  name: LocalizedText;
  description: LocalizedText;
  base_price: number;
  compare_at_price: number | null;
  status: "draft" | "active" | "archived";
  featured: boolean;
  seo_title: string | null;
  seo_description: string | null;
  avg_rating: number | null;
  review_count: number | null;
  images: { id: string; url: string; alt_text: string | null; sort_order: number }[];
  variants: AdminVariant[];
  created_at: string;
  updated_at: string;
};

export type AdminVariant = {
  id: string;
  sku: string;
  color: string | null;
  size: string | null;
  price_override: number | null;
  compare_at_price: number | null;
  size_value: number | null;
  size_unit: string | null;
  sort_order: number;
  is_active: boolean;
  weight: number | null;
  stock_quantity: number;
  reserved_quantity: number;
  available_quantity: number;
  low_stock_threshold: number;
};

export function getAdminProducts(
  params: { q?: string; status?: string; category?: string; page?: number } = {},
): Promise<{ items: AdminProduct[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.q) search.set("q", params.q);
  if (params.status) search.set("status", params.status);
  if (params.category) search.set("category", params.category);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/products${query ? `?${query}` : ""}`);
}

export function getAdminProduct(id: string): Promise<AdminProduct> {
  return adminFetch(`/admin/products/${encodeURIComponent(id)}`);
}

export type CreateProductPayload = {
  category_id: string;
  name_ar: string;
  name_fr: string;
  name_en: string;
  description_ar?: string;
  description_fr?: string;
  description_en?: string;
  base_price: number;
  compare_at_price?: number | null;
  status: "draft" | "active" | "archived";
  featured?: boolean;
  seo_title?: string | null;
  seo_description?: string | null;
  sku: string;
  initial_stock: number;
  low_stock_threshold?: number;
  size_value?: number | null;
  size_unit?: string | null;
};

export function createAdminProduct(payload: CreateProductPayload): Promise<AdminProduct> {
  return adminFetch("/admin/products", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminProduct(id: string, payload: Partial<CreateProductPayload>): Promise<AdminProduct> {
  return adminFetch(`/admin/products/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function archiveAdminProduct(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/products/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export async function uploadProductImage(
  productId: string,
  file: File,
  altText?: string,
): Promise<{ id: string; url: string; alt_text: string | null; sort_order: number }> {
  const formData = new FormData();
  formData.append("image", file);
  if (altText) formData.append("alt_text", altText);

  const { access } = getAdminTokens();

  return apiFetch(`/admin/products/${encodeURIComponent(productId)}/images`, {
    method: "POST",
    headers: { Authorization: `Bearer ${access ?? ""}` },
    body: formData,
  });
}

export function deleteProductImage(productId: string, imageId: string): Promise<{ message: string }> {
  return adminFetch(`/admin/products/${encodeURIComponent(productId)}/images/${encodeURIComponent(imageId)}`, {
    method: "DELETE",
  });
}

export function updateProductImage(
  productId: string,
  imageId: string,
  altText: string,
): Promise<{ id: string; url: string; alt_text: string | null; sort_order: number }> {
  return adminFetch(`/admin/products/${encodeURIComponent(productId)}/images/${encodeURIComponent(imageId)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ alt_text: altText }),
  });
}

export function reorderProductImages(
  productId: string,
  imageIds: string[],
): Promise<{ items: { id: string; url: string; alt_text: string | null; sort_order: number }[] }> {
  return adminFetch(`/admin/products/${encodeURIComponent(productId)}/images/reorder`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ image_ids: imageIds }),
  });
}

export type VariantPayload = {
  sku: string;
  color?: string | null;
  size?: string | null;
  price_override?: number | null;
  compare_at_price?: number | null;
  size_value?: number | null;
  size_unit?: string | null;
  sort_order?: number;
  is_active?: boolean;
  weight?: number | null;
  low_stock_threshold?: number;
  initial_stock: number;
};

export function createProductVariant(
  productId: string,
  payload: VariantPayload,
): Promise<AdminProduct> {
  return adminFetch(`/admin/products/${encodeURIComponent(productId)}/variants`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateProductVariant(
  productId: string,
  variantId: string,
  payload: Partial<Omit<VariantPayload, "initial_stock">>,
): Promise<AdminProduct> {
  return adminFetch(
    `/admin/products/${encodeURIComponent(productId)}/variants/${encodeURIComponent(variantId)}`,
    { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) },
  );
}

export function deleteProductVariant(productId: string, variantId: string): Promise<AdminProduct> {
  return adminFetch(
    `/admin/products/${encodeURIComponent(productId)}/variants/${encodeURIComponent(variantId)}`,
    { method: "DELETE" },
  );
}

export function adjustInventory(
  variantId: string,
  delta: number,
  reason: "restock" | "correction" | "return" | "damage",
): Promise<{ variant_id: string; stock_quantity: number; reserved_quantity: number; available_quantity: number }> {
  return adminFetch(`/admin/inventory/${encodeURIComponent(variantId)}/adjust`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ delta, reason }),
  });
}

export type AdminCategory = {
  id: string;
  parent_id: string | null;
  slug: string;
  name: LocalizedText;
  image_url: string | null;
  sort_order: number;
  is_active: boolean;
  seo_title: string | null;
  seo_description: string | null;
  product_count: number;
};

export function getAdminCategories(): Promise<AdminCategory[]> {
  return adminFetch("/admin/categories");
}

export type CreateCategoryPayload = {
  parent_id?: string | null;
  name_ar: string;
  name_fr: string;
  name_en: string;
  sort_order?: number;
  is_active?: boolean;
  seo_title?: string;
  seo_description?: string;
};

export function createAdminCategory(payload: CreateCategoryPayload): Promise<AdminCategory> {
  return adminFetch("/admin/categories", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminCategory(id: string, payload: Partial<CreateCategoryPayload>): Promise<AdminCategory> {
  return adminFetch(`/admin/categories/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminCategory(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/categories/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export type AdminCustomer = {
  id: string;
  full_name: string;
  phone: string;
  email: string | null;
  status: "active" | "blocked";
  phone_verified: boolean;
  created_at: string;
};

export function getAdminCustomers(
  params: { q?: string; page?: number } = {},
): Promise<{ items: AdminCustomer[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.q) search.set("q", params.q);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/customers${query ? `?${query}` : ""}`);
}

export function getAdminCustomer(
  id: string,
): Promise<AdminCustomer & { orders_available: false; orders: [] }> {
  return adminFetch(`/admin/customers/${encodeURIComponent(id)}`);
}

export function updateAdminCustomerStatus(id: string, status: "active" | "blocked"): Promise<AdminCustomer> {
  return adminFetch(`/admin/customers/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status }),
  });
}

// --- Admin: Roles & Permissions, Staff ---

export type AdminRole = {
  id: string;
  name: string;
  description: string | null;
  staff_count: number;
  permission_keys: string[];
};

export type AdminPermission = {
  id: string;
  key: string;
  description: string | null;
};

export function getAdminRoles(): Promise<AdminRole[]> {
  return adminFetch("/admin/roles");
}

export function getAdminRole(id: string): Promise<AdminRole> {
  return adminFetch(`/admin/roles/${encodeURIComponent(id)}`);
}

export function updateAdminRolePermissions(id: string, permissionKeys: string[]): Promise<AdminRole> {
  return adminFetch(`/admin/roles/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ permission_keys: permissionKeys }),
  });
}

export function getAdminPermissions(): Promise<AdminPermission[]> {
  return adminFetch("/admin/permissions");
}

export type AdminStaff = {
  id: string;
  full_name: string;
  phone: string;
  email: string | null;
  status: "active" | "blocked";
  role: { id: string; name: string } | null;
  created_at: string;
};

export function getAdminStaff(
  params: { q?: string; role_id?: string; page?: number } = {},
): Promise<{ items: AdminStaff[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.q) search.set("q", params.q);
  if (params.role_id) search.set("role_id", params.role_id);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/staff${query ? `?${query}` : ""}`);
}

export function createAdminStaff(payload: {
  full_name: string;
  phone: string;
  password: string;
  password_confirmation: string;
  role_id: string;
}): Promise<AdminStaff> {
  return adminFetch("/admin/staff", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminStaff(
  id: string,
  payload: { role_id?: string; status?: "active" | "blocked" },
): Promise<AdminStaff> {
  return adminFetch(`/admin/staff/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

// --- Checkout & Orders ---
// Guest-only for now (no customer login/register UI exists yet on the
// storefront) — checkout, order confirmation, and order tracking all work
// off the same X-Guest-Session identity as the cart.

export type Wilaya = { code: string; name: LocalizedText };

export function getWilayas(): Promise<Wilaya[]> {
  return apiFetch<Wilaya[]>("/wilayas");
}

export type Commune = { id: string; name: LocalizedText };

export function getCommunes(wilayaCode: string): Promise<Commune[]> {
  return apiFetch<Commune[]>(`/communes?wilaya_code=${encodeURIComponent(wilayaCode)}`);
}

export type DeliveryFeeLookup = {
  wilaya_code: string;
  home: { fee: number; eta_min_days: number | null; eta_max_days: number | null } | null;
  pickup: null;
};

export function getDeliveryFee(wilayaCode: string): Promise<DeliveryFeeLookup> {
  return apiFetch<DeliveryFeeLookup>(`/delivery-fees/${encodeURIComponent(wilayaCode)}`);
}

export type OrderItemDetail = {
  id: string;
  variant_id: string;
  product_name: string;
  sku: string;
  size: string | null;
  unit_price: number;
  quantity: number;
  line_total: number;
};

export type OrderStatus =
  | "pending" | "confirmed" | "processing" | "ready_to_ship" | "shipped"
  | "out_for_delivery" | "delivered" | "cancelled" | "failed" | "returned"
  | "refunded" | "partially_refunded";

export type Order = {
  id: string;
  order_number: string;
  order_status: OrderStatus;
  payment_status: string;
  payment_method: "cod" | "cib" | "edahabia" | "whatsapp";
  whatsapp_status: "pending" | "opened" | null;
  delivery_method: "home" | "pickup";
  subtotal: number;
  discount_total: number;
  delivery_fee: number;
  tax_total: number;
  grand_total: number;
  coupon: { code: string; type: string } | null;
  notes: string | null;
  customer: { name: string; phone: string; email: string | null };
  address?: {
    full_name: string;
    phone: string;
    wilaya: { code: string; name: LocalizedText | null };
    commune: LocalizedText | null;
    address_line: string;
    landmark: string | null;
    postal_code: string | null;
  };
  items?: OrderItemDetail[];
  status_history?: { status: OrderStatus; note: string | null; created_at: string }[];
  payments?: {
    id: string;
    provider: "cod" | "cib" | "edahabia";
    transaction_ref: string | null;
    status: string;
    amount: number;
    currency: string;
    created_at: string;
    updated_at: string;
  }[];
  shipments?: ShipmentDetail[];
  created_at: string;
  updated_at: string;
};

export type ShipmentStatus =
  | "pending" | "picked_up" | "in_transit" | "out_for_delivery" | "delivered" | "failed" | "returned";

export type ShipmentDetail = {
  id: string;
  courier_partner: string | null;
  tracking_number: string | null;
  status: ShipmentStatus;
  estimated_delivery_date: string | null;
  delivered_at: string | null;
  failure_reason: string | null;
  created_at: string;
  updated_at: string;
};

type CheckoutBase = {
  customer_name: string;
  customer_phone: string;
  customer_email?: string;
  delivery_method: "home";
  payment_method: "cod" | "whatsapp";
  notes?: string;
  locale: "ar" | "fr" | "en";
};

export type CheckoutPayload =
  | (CheckoutBase & {
      address: {
        full_name: string;
        phone: string;
        wilaya_code: string;
        commune_id: string;
        address_line: string;
        landmark?: string;
        postal_code?: string;
      };
    })
  | (CheckoutBase & { address_id: string });

export type WhatsAppCheckoutInfo = { phone: string; message: string; url: string };

export function checkout(payload: CheckoutPayload): Promise<{ order: Order; whatsapp: WhatsAppCheckoutInfo | null }> {
  return cartFetch<{ order: Order; whatsapp: WhatsAppCheckoutInfo | null }>("/checkout", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

/**
 * Best-effort, fire-and-forget signal that the browser attempted the wa.me
 * redirect — bypasses apiFetch's envelope handling on purpose, the response
 * is never read, and a failure here must never block or affect the redirect
 * itself (see ShabaLoaderOverlay's own fire-and-forget conventions).
 */
export function notifyWhatsAppOpened(orderNumber: string, phone: string): void {
  fetch(`${API_URL}/checkout/${encodeURIComponent(orderNumber)}/whatsapp-opened`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ phone }),
    keepalive: true,
  }).catch(() => {});
}

export function trackOrder(orderNumber: string, phone: string): Promise<Order> {
  const search = new URLSearchParams({ order_number: orderNumber, phone });

  return apiFetch<Order>(`/orders/track?${search.toString()}`);
}

// --- Admin: Orders & Delivery Fees ---

export function getAdminOrders(
  params: { status?: string; q?: string; page?: number } = {},
): Promise<{ items: Order[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.status) search.set("status", params.status);
  if (params.q) search.set("q", params.q);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/orders${query ? `?${query}` : ""}`);
}

export function getAdminOrder(id: string): Promise<Order> {
  return adminFetch(`/admin/orders/${encodeURIComponent(id)}`);
}

export function updateAdminOrderStatus(id: string, status: OrderStatus, note?: string): Promise<Order> {
  return adminFetch(`/admin/orders/${encodeURIComponent(id)}/status`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status, note }),
  });
}

export function createAdminShipment(
  orderId: string,
  payload: { courier_partner?: string; tracking_number?: string; estimated_delivery_date?: string },
): Promise<ShipmentDetail> {
  return adminFetch(`/admin/orders/${encodeURIComponent(orderId)}/shipments`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminShipmentStatus(
  shipmentId: string,
  payload: {
    status: Exclude<ShipmentStatus, "pending">;
    courier_partner?: string;
    tracking_number?: string;
    estimated_delivery_date?: string;
    failure_reason?: string;
  },
): Promise<ShipmentDetail> {
  return adminFetch(`/admin/shipments/${encodeURIComponent(shipmentId)}/status`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export type AdminDeliveryFee = {
  wilaya_code: string;
  wilaya_name: LocalizedText;
  fee: number;
  eta_min_days: number | null;
  eta_max_days: number | null;
};

export function getAdminDeliveryFees(): Promise<AdminDeliveryFee[]> {
  return adminFetch("/admin/delivery-fees");
}

export function updateAdminDeliveryFee(
  wilayaCode: string,
  payload: { fee: number; eta_min_days?: number | null; eta_max_days?: number | null },
): Promise<AdminDeliveryFee> {
  return adminFetch(`/admin/delivery-fees/${encodeURIComponent(wilayaCode)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

// --- Customer auth ---
// Mirrors the admin auth block above exactly (same token-pair/refresh-retry
// pattern), under distinct storage keys (lib/customer-session.ts).

export type CustomerUser = {
  id: string;
  full_name: string;
  phone: string;
  email: string | null;
  preferred_language: string;
  has_password?: boolean;
};

// Discriminated union matching exactly what LoginController/SocialAuthService
// return on the backend: either a full session, or (2FA-enabled account) a
// short-lived pending token that must go through customerVerifyTwoFactor()
// before any real tokens exist. customerLogin/customerRegister/
// exchangeSocialCode all funnel through this same shape — previously
// customerLogin/customerRegister assumed a full pair unconditionally, which
// would have silently stored "undefined" tokens for a 2FA-enabled customer;
// this union makes that case impossible to ignore by accident.
export type CustomerSessionResult =
  | ({ requires_two_factor?: false; user: CustomerUser } & TokenPair)
  | { requires_two_factor: true; two_factor_token: string; expires_in: number };

export async function customerRegister(payload: {
  full_name: string;
  phone: string;
  email?: string;
  password: string;
  password_confirmation: string;
}): Promise<CustomerSessionResult> {
  const data = await apiFetch<CustomerSessionResult>("/auth/register", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!data.requires_two_factor) {
    setCustomerTokens(data.access_token, data.refresh_token);
  }

  return data;
}

export async function customerLogin(login: string, password: string): Promise<CustomerSessionResult> {
  const data = await apiFetch<CustomerSessionResult>("/auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ login, password }),
  });

  if (!data.requires_two_factor) {
    setCustomerTokens(data.access_token, data.refresh_token);
  }

  return data;
}

export async function customerVerifyTwoFactor(twoFactorToken: string, code: string): Promise<{ user: CustomerUser } & TokenPair> {
  const data = await apiFetch<{ user: CustomerUser } & TokenPair>("/auth/2fa/verify", {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${twoFactorToken}` },
    body: JSON.stringify({ code }),
  });

  setCustomerTokens(data.access_token, data.refresh_token);

  return data;
}

// Password reset is phone+OTP based (same OtpService/purpose mechanism as
// login-by-OTP), not an email link — matches the real backend contract
// (PasswordResetController/OtpController), not a generic "reset token" flow.
export function sendPasswordResetOtp(phone: string): Promise<{ message: string }> {
  return apiFetch("/auth/otp/send", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ phone, purpose: "password_reset" }),
  });
}

export function resetPassword(payload: {
  phone: string;
  code: string;
  new_password: string;
  new_password_confirmation: string;
}): Promise<{ message: string }> {
  return apiFetch("/auth/password/reset", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export async function customerLogout(): Promise<void> {
  const { access } = getCustomerTokens();

  if (access) {
    await apiFetch("/auth/logout", {
      method: "POST",
      headers: { Authorization: `Bearer ${access}` },
    }).catch(() => undefined);
  }

  clearCustomerTokens();
}

async function refreshCustomerSession(): Promise<string | null> {
  const { refresh } = getCustomerTokens();
  if (!refresh) {
    return null;
  }

  try {
    const data = await apiFetch<TokenPair>("/auth/refresh", {
      method: "POST",
      headers: { Authorization: `Bearer ${refresh}` },
    });
    setCustomerTokens(data.access_token, data.refresh_token);

    return data.access_token;
  } catch {
    clearCustomerTokens();

    return null;
  }
}

// Same in-flight-dedup fix as adminRefreshPromise above, for the same
// reason: concurrent customer requests (account page firing off orders +
// notifications at once) can otherwise both race a single-use refresh
// token and log the customer out despite the refresh succeeding.
let customerRefreshPromise: Promise<string | null> | null = null;

function refreshCustomerSessionOnce(): Promise<string | null> {
  if (!customerRefreshPromise) {
    customerRefreshPromise = refreshCustomerSession().finally(() => {
      customerRefreshPromise = null;
    });
  }

  return customerRefreshPromise;
}

async function customerFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const { access } = getCustomerTokens();

  try {
    return await apiFetch<T>(path, {
      ...init,
      headers: { Authorization: `Bearer ${access ?? ""}`, ...init?.headers },
    });
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      const refreshedAccess = await refreshCustomerSessionOnce();
      if (refreshedAccess) {
        return apiFetch<T>(path, {
          ...init,
          headers: { Authorization: `Bearer ${refreshedAccess}`, ...init?.headers },
        });
      }
    }
    throw error;
  }
}

// --- Social login (Google/Facebook/Apple) ---
// Fully backend-redirect-driven, no client-side provider SDK — the
// frontend never talks to Google/Facebook/Apple directly, matching the
// same "thin lib/api.ts wrapper" pattern as every other auth flow here.
// "Continue with X" is a plain <a href> to socialRedirectUrl(), a real
// top-level browser navigation (not fetch, so no header can ride along) —
// exchangeSocialCode() is the only place real tokens are ever transmitted,
// once the backend's callback has redirected back with a short-lived
// one-time code.

export type SocialProvider = "google" | "facebook" | "apple";

export function socialRedirectUrl(provider: SocialProvider, returnTo: string, locale: string): string {
  const apiOrigin = API_URL.replace(/\/api\/v1$/, "");
  const params = new URLSearchParams({ return_to: sanitizeReturnTo(returnTo), locale });

  return `${apiOrigin}/api/v1/auth/${provider}/redirect?${params.toString()}`;
}

/**
 * Same relative-path-only rule the backend enforces before it ever embeds
 * this value in the encrypted OAuth state (SocialRedirectController's own
 * safeReturnTo()) — never trust the client-side check alone, but applying
 * it here too means a malformed value never even gets sent, and the post-
 * login navigation (which does trust this function's output directly)
 * can't be tricked into an off-site redirect either.
 */
export function sanitizeReturnTo(value: string | null | undefined): string {
  if (!value || !value.startsWith("/") || value.startsWith("//") || value.includes("://")) {
    return "/";
  }

  return value;
}

export function exchangeSocialCode(code: string): Promise<CustomerSessionResult> {
  return apiFetch<CustomerSessionResult>("/auth/social/exchange", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ code }),
  }).then((data) => {
    if (!data.requires_two_factor) {
      setCustomerTokens(data.access_token, data.refresh_token);
    }

    return data;
  });
}

export type SocialAccountStatus = { provider: SocialProvider; connected: boolean; email: string | null };

export function getSocialAccounts(): Promise<{ accounts: SocialAccountStatus[] }> {
  return customerFetch("/users/me/social-accounts");
}

export function unlinkSocialAccount(provider: SocialProvider): Promise<{ message: string }> {
  return customerFetch(`/users/me/social-accounts/${provider}`, { method: "DELETE" });
}

export function getSocialLinkUrl(provider: SocialProvider): Promise<{ redirect_url: string }> {
  return customerFetch(`/users/me/social-accounts/${provider}/link-token`, { method: "POST" });
}

export function setInitialPassword(payload: { password: string; password_confirmation: string }): Promise<{ message: string }> {
  return customerFetch("/users/me/password/set", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

// --- Cart merge (guest cart -> account, on login/register/social-login) ---

export function mergeGuestCart(guestSessionToken: string): Promise<CartData> {
  return customerFetch("/cart/merge", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ guest_session_token: guestSessionToken }),
  });
}

// --- My Orders (customer, authenticated) ---

export function getMyOrders(page = 1): Promise<{ items: Order[]; meta: ProductListMeta }> {
  return customerFetch(`/orders?page=${page}`);
}

export function getMyOrder(orderNumber: string): Promise<Order> {
  return customerFetch(`/orders/${encodeURIComponent(orderNumber)}`);
}

// --- Notifications (customer, authenticated) ---

export type NotificationEventType =
  | "order_created" | "order_shipped" | "order_delivered" | "order_cancelled"
  | "payment_successful" | "payment_failed";

export type NotificationItem = {
  id: string;
  event_type: NotificationEventType | string;
  payload: { order_id?: string; order_number?: string; payment_id?: string } | null;
  read_at: string | null;
  created_at: string;
};

export function getNotifications(
  page = 1,
): Promise<{ items: NotificationItem[]; unread_count: number; meta: ProductListMeta }> {
  return customerFetch(`/notifications?page=${page}`);
}

export function markNotificationRead(id: string): Promise<NotificationItem> {
  return customerFetch(`/notifications/${encodeURIComponent(id)}/read`, { method: "PATCH" });
}

export function markAllNotificationsRead(): Promise<{ message: string }> {
  return customerFetch("/notifications/read-all", { method: "PATCH" });
}

export function deleteNotification(id: string): Promise<{ message: string }> {
  return customerFetch(`/notifications/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Reviews ---

export type Review = {
  id: string;
  product_id: string;
  rating: number;
  title: string | null;
  body: string | null;
  is_verified_purchase: boolean;
  status: "pending" | "approved" | "rejected";
  reviewer_name?: string;
  images: { id: string; url: string }[];
  report_count?: number;
  created_at: string;
};

export function getProductReviews(
  slug: string,
  page = 1,
): Promise<{ items: Review[]; meta: ProductListMeta }> {
  return apiFetch(`/products/${encodeURIComponent(slug)}/reviews?page=${page}`);
}

export function getMyReview(slug: string): Promise<Review | null> {
  return customerFetch(`/products/${encodeURIComponent(slug)}/reviews/mine`);
}

export function submitReview(
  slug: string,
  payload: { rating: number; title?: string; body?: string },
): Promise<Review> {
  return customerFetch(`/products/${encodeURIComponent(slug)}/reviews`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteReview(id: string): Promise<{ message: string }> {
  return customerFetch(`/reviews/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export async function uploadReviewImage(reviewId: string, file: File): Promise<{ id: string; url: string }> {
  const formData = new FormData();
  formData.append("image", file);

  const { access } = getCustomerTokens();

  return apiFetch(`/reviews/${encodeURIComponent(reviewId)}/images`, {
    method: "POST",
    headers: { Authorization: `Bearer ${access ?? ""}` },
    body: formData,
  });
}

export function reportReview(id: string, reason: string): Promise<{ message: string }> {
  return customerFetch(`/reviews/${encodeURIComponent(id)}/report`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ reason }),
  });
}

// --- Admin: Reviews ---

export function getAdminReviews(
  params: { status?: string; page?: number } = {},
): Promise<{ items: (Review & { product: { id: string; slug: string; name: LocalizedText } })[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.status) search.set("status", params.status);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/reviews${query ? `?${query}` : ""}`);
}

export function updateAdminReviewStatus(id: string, status: "approved" | "rejected"): Promise<Review> {
  return adminFetch(`/admin/reviews/${encodeURIComponent(id)}/status`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status }),
  });
}

// --- Admin: Coupons ---

export type AdminCoupon = {
  id: string;
  code: string;
  type: "percentage" | "fixed" | "free_shipping";
  value: number | null;
  min_order_value: number | null;
  start_date: string | null;
  end_date: string | null;
  usage_limit_total: number | null;
  usage_limit_per_customer: number | null;
  usage_count: number;
  is_active: boolean;
};

export type CouponPayload = {
  code: string;
  type: "percentage" | "fixed" | "free_shipping";
  value?: number | null;
  min_order_value?: number | null;
  start_date?: string | null;
  end_date?: string | null;
  usage_limit_total?: number | null;
  usage_limit_per_customer?: number | null;
  is_active?: boolean;
};

export function getAdminCoupons(
  params: { q?: string; page?: number } = {},
): Promise<{ items: AdminCoupon[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.q) search.set("q", params.q);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/coupons${query ? `?${query}` : ""}`);
}

export function createAdminCoupon(payload: CouponPayload): Promise<AdminCoupon> {
  return adminFetch("/admin/coupons", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminCoupon(id: string, payload: Partial<CouponPayload>): Promise<AdminCoupon> {
  return adminFetch(`/admin/coupons/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deactivateAdminCoupon(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/coupons/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Admin: Payments ---

export type ReconciliationStatus = "unreconciled" | "reconciled" | "disputed";

export type AdminPayment = {
  id: string;
  order_id: string;
  order_number: string | null;
  provider: "cod" | "cib" | "edahabia";
  transaction_ref: string | null;
  status: string;
  amount: number;
  currency: string;
  reconciliation_status: ReconciliationStatus;
  reconciled_at: string | null;
  reconciled_by: string | null;
  reconciliation_notes: string | null;
  created_at: string;
  updated_at: string;
};

export function getAdminPayments(
  params: { provider?: string; status?: string; reconciliation_status?: string; page?: number } = {},
): Promise<{ items: AdminPayment[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.provider) search.set("provider", params.provider);
  if (params.status) search.set("status", params.status);
  if (params.reconciliation_status) search.set("reconciliation_status", params.reconciliation_status);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/payments${query ? `?${query}` : ""}`);
}

export function reconcileAdminPayment(
  id: string,
  payload: { reconciliation_status: ReconciliationStatus; notes?: string },
): Promise<AdminPayment> {
  return adminFetch(`/admin/payments/${encodeURIComponent(id)}/reconcile`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

// --- Customer account: profile, password, saved addresses ---

export function updateProfile(payload: {
  full_name?: string;
  email?: string | null;
  preferred_language?: "ar" | "fr" | "en";
}): Promise<CustomerUser> {
  return customerFetch("/users/me", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function changePassword(payload: {
  current_password: string;
  password: string;
  password_confirmation: string;
}): Promise<{ message: string }> {
  return customerFetch("/users/me/password", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export type Address = {
  id: string;
  full_name: string;
  phone: string;
  wilaya_code: string;
  commune_id: string;
  address_line: string;
  landmark: string | null;
  postal_code: string | null;
  is_default: boolean;
};

export type AddressPayload = {
  full_name: string;
  phone: string;
  wilaya_code: string;
  commune_id: string;
  address_line: string;
  landmark?: string;
  postal_code?: string;
  is_default?: boolean;
};

export function getAddresses(): Promise<Address[]> {
  return customerFetch("/addresses");
}

export function createAddress(payload: AddressPayload): Promise<Address> {
  return customerFetch("/addresses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAddress(id: string, payload: Partial<AddressPayload>): Promise<Address> {
  return customerFetch(`/addresses/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAddress(id: string): Promise<{ message: string }> {
  return customerFetch(`/addresses/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Footer CMS (public) ---

type NullableLocalizedText = { ar: string | null; fr: string | null; en: string | null };

export type FooterData = {
  settings: {
    about: { title: NullableLocalizedText; description: NullableLocalizedText };
    whatsapp: { number: string | null; orders_enabled: boolean; message: NullableLocalizedText };
  };
  features: { id: string; icon: string; title: LocalizedText; description: NullableLocalizedText }[];
  socialLinks: { platform: string; url: string }[];
  columns: { id: string; title: LocalizedText; links: { label: LocalizedText; url: string }[] }[];
  paymentMethods: { name: LocalizedText; icon: string }[];
};

export function getFooterData(): Promise<FooterData> {
  return apiFetch("/footer");
}

export function subscribeNewsletter(email: string, locale: string): Promise<{ id: string; email: string }> {
  return apiFetch("/newsletter/subscribe", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, locale }),
  });
}

export function unsubscribeNewsletter(token: string): Promise<{ unsubscribed: true }> {
  return apiFetch(`/newsletter/unsubscribe/${encodeURIComponent(token)}`, { method: "DELETE" });
}

// --- Admin: Footer features ---

export type AdminFooterFeature = {
  id: string;
  icon: string;
  title: LocalizedText;
  description: NullableLocalizedText;
  is_active: boolean;
  sort_order: number;
};

export type FooterFeaturePayload = {
  icon: string;
  title_ar: string;
  title_fr: string;
  title_en: string;
  description_ar?: string | null;
  description_fr?: string | null;
  description_en?: string | null;
  is_active?: boolean;
  sort_order?: number;
};

export function getAdminFooterFeatures(): Promise<AdminFooterFeature[]> {
  return adminFetch("/admin/footer/features");
}

export function createAdminFooterFeature(payload: FooterFeaturePayload): Promise<AdminFooterFeature> {
  return adminFetch("/admin/footer/features", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminFooterFeature(id: string, payload: Partial<FooterFeaturePayload>): Promise<AdminFooterFeature> {
  return adminFetch(`/admin/footer/features/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminFooterFeature(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/footer/features/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Admin: Footer columns & links ---

export type AdminFooterLink = {
  id: string;
  footer_column_id: string;
  label: LocalizedText;
  url: string;
  is_active: boolean;
  sort_order: number;
};

export type AdminFooterColumn = {
  id: string;
  title: LocalizedText;
  is_active: boolean;
  sort_order: number;
  links: AdminFooterLink[];
};

export type FooterColumnPayload = { title_ar: string; title_fr: string; title_en: string; is_active?: boolean; sort_order?: number };

export type FooterLinkPayload = {
  label_ar: string;
  label_fr: string;
  label_en: string;
  url: string;
  is_active?: boolean;
  sort_order?: number;
};

export function getAdminFooterColumns(): Promise<AdminFooterColumn[]> {
  return adminFetch("/admin/footer/columns");
}

export function createAdminFooterColumn(payload: FooterColumnPayload): Promise<AdminFooterColumn> {
  return adminFetch("/admin/footer/columns", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminFooterColumn(id: string, payload: Partial<FooterColumnPayload>): Promise<AdminFooterColumn> {
  return adminFetch(`/admin/footer/columns/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminFooterColumn(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/footer/columns/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export function createAdminFooterLink(columnId: string, payload: FooterLinkPayload): Promise<AdminFooterLink> {
  return adminFetch(`/admin/footer/columns/${encodeURIComponent(columnId)}/links`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminFooterLink(id: string, payload: Partial<FooterLinkPayload>): Promise<AdminFooterLink> {
  return adminFetch(`/admin/footer/links/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminFooterLink(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/footer/links/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Admin: Footer social links ---

export type AdminFooterSocialLink = {
  id: string;
  platform: string;
  url: string;
  is_active: boolean;
  sort_order: number;
};

export type FooterSocialLinkPayload = { platform: string; url: string; is_active?: boolean; sort_order?: number };

export function getAdminFooterSocialLinks(): Promise<AdminFooterSocialLink[]> {
  return adminFetch("/admin/footer/social-links");
}

export function createAdminFooterSocialLink(payload: FooterSocialLinkPayload): Promise<AdminFooterSocialLink> {
  return adminFetch("/admin/footer/social-links", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminFooterSocialLink(
  id: string,
  payload: Partial<FooterSocialLinkPayload>,
): Promise<AdminFooterSocialLink> {
  return adminFetch(`/admin/footer/social-links/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminFooterSocialLink(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/footer/social-links/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Admin: Footer payment methods ---

export type AdminFooterPaymentMethod = {
  id: string;
  name: LocalizedText;
  icon: string;
  is_active: boolean;
  sort_order: number;
};

export type FooterPaymentMethodPayload = {
  name_ar: string;
  name_fr: string;
  name_en: string;
  icon: string;
  is_active?: boolean;
  sort_order?: number;
};

export function getAdminFooterPaymentMethods(): Promise<AdminFooterPaymentMethod[]> {
  return adminFetch("/admin/footer/payment-methods");
}

export function createAdminFooterPaymentMethod(payload: FooterPaymentMethodPayload): Promise<AdminFooterPaymentMethod> {
  return adminFetch("/admin/footer/payment-methods", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminFooterPaymentMethod(
  id: string,
  payload: Partial<FooterPaymentMethodPayload>,
): Promise<AdminFooterPaymentMethod> {
  return adminFetch(`/admin/footer/payment-methods/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminFooterPaymentMethod(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/footer/payment-methods/${encodeURIComponent(id)}`, { method: "DELETE" });
}

// --- Admin: Newsletter subscribers (read-only) ---

export type AdminNewsletterSubscriber = { id: string; email: string; locale: string | null; subscribed_at: string };

export function getAdminNewsletterSubscribers(
  params: { page?: number } = {},
): Promise<{ items: AdminNewsletterSubscriber[]; meta: ProductListMeta }> {
  const query = params.page ? `?page=${params.page}` : "";

  return adminFetch(`/admin/newsletter-subscribers${query}`);
}

// --- Admin: Hero slider ---

export type AdminHeroSlide = {
  id: string;
  product: { id: string; slug: string; name: LocalizedText; image_url: string | null } | null;
  image_url: string | null;
  mobile_image_url: string | null;
  title: NullableLocalizedText;
  subtitle: NullableLocalizedText;
  cta_label: NullableLocalizedText;
  is_active: boolean;
  sort_order: number;
  start_date: string | null;
  end_date: string | null;
};

export type HeroSlidePayload = {
  product_id: string;
  title_ar?: string | null;
  title_fr?: string | null;
  title_en?: string | null;
  subtitle_ar?: string | null;
  subtitle_fr?: string | null;
  subtitle_en?: string | null;
  cta_label_ar?: string | null;
  cta_label_fr?: string | null;
  cta_label_en?: string | null;
  is_active?: boolean;
  start_date?: string | null;
  end_date?: string | null;
};

export function getAdminHeroSlides(): Promise<AdminHeroSlide[]> {
  return adminFetch("/admin/hero-slides");
}

export function createAdminHeroSlide(payload: HeroSlidePayload): Promise<AdminHeroSlide> {
  return adminFetch("/admin/hero-slides", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function updateAdminHeroSlide(id: string, payload: Partial<HeroSlidePayload>): Promise<AdminHeroSlide> {
  return adminFetch(`/admin/hero-slides/${encodeURIComponent(id)}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function deleteAdminHeroSlide(id: string): Promise<{ message: string }> {
  return adminFetch(`/admin/hero-slides/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export function reorderAdminHeroSlides(slideIds: string[]): Promise<AdminHeroSlide[]> {
  return adminFetch("/admin/hero-slides/reorder", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ slide_ids: slideIds }),
  });
}

export async function uploadHeroSlideImage(
  slideId: string,
  file: File,
  variant: "desktop" | "mobile",
): Promise<AdminHeroSlide> {
  const formData = new FormData();
  formData.append("image", file);

  const { access } = getAdminTokens();
  const path = variant === "desktop" ? "image" : "mobile-image";

  return apiFetch(`/admin/hero-slides/${encodeURIComponent(slideId)}/${path}`, {
    method: "POST",
    headers: { Authorization: `Bearer ${access ?? ""}` },
    body: formData,
  });
}

// --- Admin: Audit Log ---

export type AuditLogEntry = {
  id: string;
  actor_name: string;
  action: string;
  entity_type: string;
  entity_id: string;
  subject_label: string;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  created_at: string;
};

export function getAuditLogs(
  params: { actor_id?: string; action?: string; date_from?: string; date_to?: string; page?: number } = {},
): Promise<{ items: AuditLogEntry[]; meta: ProductListMeta }> {
  const search = new URLSearchParams();
  if (params.actor_id) search.set("actor_id", params.actor_id);
  if (params.action) search.set("action", params.action);
  if (params.date_from) search.set("date_from", params.date_from);
  if (params.date_to) search.set("date_to", params.date_to);
  if (params.page) search.set("page", String(params.page));

  const query = search.toString();

  return adminFetch(`/admin/audit-logs${query ? `?${query}` : ""}`);
}

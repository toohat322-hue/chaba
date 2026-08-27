import { afterEach, describe, expect, it, vi } from "vitest";
import { apiFetch, ApiError, getCart } from "./api";

describe("apiFetch", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("unwraps the {success,data,error} envelope and returns just the data", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      json: () => Promise.resolve({ success: true, data: { id: "1" }, error: null }),
    });
    vi.stubGlobal("fetch", fetchMock);

    const result = await apiFetch<{ id: string }>("/products/1");

    expect(result).toEqual({ id: "1" });
  });

  it("throws an ApiError carrying the error code, message, and HTTP status on failure", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 404,
      json: () =>
        Promise.resolve({
          success: false,
          data: null,
          error: { code: "not_found", message: "Product not found." },
        }),
    });
    vi.stubGlobal("fetch", fetchMock);

    await expect(apiFetch("/products/missing")).rejects.toMatchObject({
      code: "not_found",
      message: "Product not found.",
      status: 404,
    });
  });

  it("throws an instance of ApiError specifically, not a generic Error", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 400,
      json: () =>
        Promise.resolve({ success: false, data: null, error: { code: "validation_error", message: "Invalid." } }),
    });
    vi.stubGlobal("fetch", fetchMock);

    await expect(apiFetch("/x")).rejects.toBeInstanceOf(ApiError);
  });

  it("carries field_errors from the envelope onto the thrown ApiError", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 400,
      json: () =>
        Promise.resolve({
          success: false,
          data: null,
          error: {
            code: "validation_error",
            message: "The sku has already been taken.",
            field_errors: { sku: ["The sku has already been taken."] },
          },
        }),
    });
    vi.stubGlobal("fetch", fetchMock);

    await expect(apiFetch("/admin/products")).rejects.toMatchObject({
      fieldErrors: { sku: ["The sku has already been taken."] },
    });
  });
});

const cartEnvelope = { success: true, data: { id: "cart-1", items: [], subtotal: 0, discount_total: 0, coupon: null, item_count: 0 }, error: null };
const guestSessionRequiredEnvelope = {
  success: false,
  data: null,
  error: { code: "guest_session_required", message: "A guest session token is required." },
};

// /cart has no auth:sanctum middleware (guests use it too), so a stale
// access token doesn't 401 — the guard just resolves no user, and since no
// X-Guest-Session was sent either (the caller thought it was
// authenticated), the backend rejects with guest_session_required instead.
describe("getCart (customerOrGuestFetch fallback)", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    window.localStorage.clear();
  });

  it("falls back to a guest session when the stored access token is stale and there's no refresh token", async () => {
    window.localStorage.setItem("chaba_customer_access_token", "stale-access-token");

    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({ status: 400, json: () => Promise.resolve(guestSessionRequiredEnvelope) })
      .mockResolvedValueOnce({ status: 200, json: () => Promise.resolve(cartEnvelope) });
    vi.stubGlobal("fetch", fetchMock);

    const result = await getCart();

    expect(result.id).toBe("cart-1");
    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(fetchMock.mock.calls[0][1].headers.Authorization).toBe("Bearer stale-access-token");
    expect(fetchMock.mock.calls[1][1].headers["X-Guest-Session"]).toBeTruthy();
  });

  it("retries with a refreshed token when the stored access token is stale but the refresh token is valid", async () => {
    window.localStorage.setItem("chaba_customer_access_token", "stale-access-token");
    window.localStorage.setItem("chaba_customer_refresh_token", "valid-refresh-token");

    const refreshEnvelope = {
      success: true,
      data: { access_token: "fresh-access-token", refresh_token: "fresh-refresh-token" },
      error: null,
    };

    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({ status: 400, json: () => Promise.resolve(guestSessionRequiredEnvelope) })
      .mockResolvedValueOnce({ status: 200, json: () => Promise.resolve(refreshEnvelope) })
      .mockResolvedValueOnce({ status: 200, json: () => Promise.resolve(cartEnvelope) });
    vi.stubGlobal("fetch", fetchMock);

    const result = await getCart();

    expect(result.id).toBe("cart-1");
    expect(fetchMock).toHaveBeenCalledTimes(3);
    expect(fetchMock.mock.calls[2][1].headers.Authorization).toBe("Bearer fresh-access-token");
    expect(window.localStorage.getItem("chaba_customer_access_token")).toBe("fresh-access-token");
  });

  it("sends the guest session header directly when there is no stored access token at all", async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce({ status: 200, json: () => Promise.resolve(cartEnvelope) });
    vi.stubGlobal("fetch", fetchMock);

    await getCart();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(fetchMock.mock.calls[0][1].headers["X-Guest-Session"]).toBeTruthy();
    expect(fetchMock.mock.calls[0][1].headers.Authorization).toBeUndefined();
  });
});

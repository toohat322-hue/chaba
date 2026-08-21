import { afterEach, describe, expect, it, vi } from "vitest";
import { apiFetch, ApiError } from "./api";

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

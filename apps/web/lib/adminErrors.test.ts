import { describe, expect, it } from "vitest";
import { ApiError } from "./api";
import { adminErrorMessage, adminFieldErrors } from "./adminErrors";

const t = (key: string) => key;

describe("adminErrorMessage", () => {
  it("maps a duplicate-sku validation error to a specific message, not the generic one", () => {
    const error = new ApiError("validation_error", "The sku has already been taken.", 400, {
      sku: ["The sku has already been taken."],
    });

    expect(adminErrorMessage(error, t)).toBe("errorSkuTaken");
  });

  it("falls back to a generic validation message for an unmapped field", () => {
    const error = new ApiError("validation_error", "The foo field is required.", 400, {
      foo: ["The foo field is required."],
    });

    expect(adminErrorMessage(error, t)).toBe("errorValidationGeneric");
  });

  it("reports a distinct message for a 5xx server error", () => {
    const error = new ApiError("server_error", "Internal error.", 500);

    expect(adminErrorMessage(error, t)).toBe("errorServer");
  });

  it("reports a distinct message when fetch itself never reached the server", () => {
    const error = new TypeError("Failed to fetch");

    expect(adminErrorMessage(error, t)).toBe("errorNetwork");
  });

  it("falls back to the generic message for a completely unexpected error shape", () => {
    expect(adminErrorMessage("not an error at all", t)).toBe("errorGeneric");
  });
});

describe("adminFieldErrors", () => {
  it("returns a field -> localized message map for known fields only", () => {
    const error = new ApiError("validation_error", "Validation failed.", 400, {
      sku: ["The sku has already been taken."],
      some_unknown_field: ["Whatever."],
    });

    expect(adminFieldErrors(error, t)).toEqual({ sku: "errorSkuTaken" });
  });

  it("returns an empty object when there are no field errors", () => {
    const error = new ApiError("not_found", "Not found.", 404);

    expect(adminFieldErrors(error, t)).toEqual({});
  });
});

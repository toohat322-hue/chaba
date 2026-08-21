import { describe, expect, it } from "vitest";
import { formatPrice } from "./currency";

// Group-separator characters differ by locale/ICU version (e.g. fr-DZ uses a
// narrow no-break space, ar-DZ a period) — computed via the same Intl call
// the source uses rather than hardcoded, so the test doesn't fight invisible
// Unicode whitespace variants.
function groupedNumber(value: number, bcp47: string): string {
  return new Intl.NumberFormat(bcp47, { maximumFractionDigits: 0 }).format(value);
}

describe("formatPrice", () => {
  it("converts centimes to whole dinars and appends the Arabic currency label", () => {
    expect(formatPrice(1000000, "ar")).toBe(`${groupedNumber(10000, "ar-DZ")} د.ج`);
  });

  it("appends the French currency label", () => {
    expect(formatPrice(1000000, "fr")).toBe(`${groupedNumber(10000, "fr-DZ")} DA`);
  });

  it("appends the English currency label", () => {
    expect(formatPrice(1000000, "en")).toBe(`${groupedNumber(10000, "en-US")} DZD`);
  });

  it("rounds to the nearest whole dinar rather than truncating", () => {
    // 1050 centimes -> 10.5 DZD, rounds up to 11.
    expect(formatPrice(1050, "en")).toBe("11 DZD");
  });

  it("formats zero without throwing", () => {
    expect(formatPrice(0, "ar")).toBe("0 د.ج");
  });
});

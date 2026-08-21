import { describe, expect, it } from "vitest";
import { render } from "@testing-library/react";
import { SOCIAL_ICONS, PAYMENT_ICONS, FEATURE_ICONS, GenericIcon } from "./FooterIcons";

// Slugs kept in sync by hand with the backend's validated `in:` lists (see
// FooterIcons.tsx's docblock) — this test guards against the two drifting:
// every slug the backend accepts must resolve to a real icon component here.
describe("FooterIcons", () => {
  it("has an icon for every social platform the backend accepts", () => {
    const platforms = ["instagram", "facebook", "tiktok", "snapchat", "twitter", "youtube", "whatsapp"];
    for (const platform of platforms) {
      expect(SOCIAL_ICONS[platform]).toBeDefined();
    }
  });

  it("has an icon for every payment method slug the backend accepts", () => {
    const icons = ["cod", "cib", "edahabia", "visa", "mastercard", "applepay", "mada", "card"];
    for (const icon of icons) {
      expect(PAYMENT_ICONS[icon]).toBeDefined();
    }
  });

  it("has an icon for every feature slug the backend accepts", () => {
    const icons = ["truck", "shield", "return", "badge", "clock", "gift", "star", "lock"];
    for (const icon of icons) {
      expect(FEATURE_ICONS[icon]).toBeDefined();
    }
  });

  it("falls back to GenericIcon for an unrecognized slug without throwing", () => {
    const Icon = SOCIAL_ICONS["unknown-platform"] ?? GenericIcon;
    const { container } = render(<Icon className="h-4 w-4" />);
    expect(container.querySelector("svg")).toBeInTheDocument();
  });
});

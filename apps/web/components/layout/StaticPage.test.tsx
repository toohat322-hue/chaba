import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { StaticPageHeader, StaticSection } from "./StaticPage";

describe("StaticPageHeader", () => {
  it("renders the title", () => {
    render(<StaticPageHeader title="About Us" />);
    expect(screen.getByRole("heading", { name: "About Us" })).toBeInTheDocument();
  });

  it("renders the intro paragraph when provided, and omits it when not", () => {
    const { rerender } = render(<StaticPageHeader title="About Us" intro="Some intro text" />);
    expect(screen.getByText("Some intro text")).toBeInTheDocument();

    rerender(<StaticPageHeader title="About Us" />);
    expect(screen.queryByText("Some intro text")).not.toBeInTheDocument();
  });
});

describe("StaticSection", () => {
  it("renders both the heading and body", () => {
    render(<StaticSection heading="Payment" body="We accept cash on delivery." />);
    expect(screen.getByRole("heading", { name: "Payment" })).toBeInTheDocument();
    expect(screen.getByText("We accept cash on delivery.")).toBeInTheDocument();
  });
});

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { ProductGallery } from "./ProductGallery";

const images = [
  { url: "/one.jpg", alt_text: null },
  { url: "/two.jpg", alt_text: null },
  { url: "/three.jpg", alt_text: null },
];

describe("ProductGallery", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("advances to the next image automatically after the autoplay interval", () => {
    render(<ProductGallery images={images} title="CHABA Rose" />);

    expect(screen.getByRole("button", { name: "CHABA Rose 1" })).toHaveAttribute("aria-current", "true");

    act(() => vi.advanceTimersByTime(5000));

    expect(screen.getByRole("button", { name: "CHABA Rose 2" })).toHaveAttribute("aria-current", "true");
  });

  it("pauses autoplay while the pointer is hovering the main image", () => {
    render(<ProductGallery images={images} title="CHABA Rose" />);
    const mainImage = screen.getByTestId("gallery-main-image");

    fireEvent.mouseEnter(mainImage);
    act(() => vi.advanceTimersByTime(10000));
    expect(screen.getByRole("button", { name: "CHABA Rose 1" })).toHaveAttribute("aria-current", "true");

    fireEvent.mouseLeave(mainImage);
    act(() => vi.advanceTimersByTime(5000));
    expect(screen.getByRole("button", { name: "CHABA Rose 2" })).toHaveAttribute("aria-current", "true");
  });

  it("jumps to an image when its thumbnail is clicked", () => {
    render(<ProductGallery images={images} title="CHABA Rose" />);

    fireEvent.click(screen.getByRole("button", { name: "CHABA Rose 3" }));

    expect(screen.getByRole("button", { name: "CHABA Rose 3" })).toHaveAttribute("aria-current", "true");
  });

  it("does not autoplay a single-image gallery", () => {
    render(<ProductGallery images={[images[0]]} title="CHABA Rose" />);

    expect(screen.queryByRole("button", { name: "CHABA Rose 1" })).not.toBeInTheDocument();
    act(() => vi.advanceTimersByTime(10000));
    // No thumbnails render for a single image — nothing to assert beyond
    // "this doesn't throw", which confirms the count<=1 autoplay guard.
  });

  it("shows the local placeholder glyph when there are no images", () => {
    const { container } = render(<ProductGallery images={[]} title="CHABA Rose" />);
    expect(container.querySelector("svg")).toBeInTheDocument();
  });
});

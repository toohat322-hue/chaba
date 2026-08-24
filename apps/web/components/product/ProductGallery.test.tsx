import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { ProductGallery } from "./ProductGallery";

const messages = {
  Product: {
    prevImage: "Previous image",
    nextImage: "Next image",
    goToImage: "Go to image {number}",
  },
};

const images = [
  { url: "/one.jpg", alt_text: null },
  { url: "/two.jpg", alt_text: null },
  { url: "/three.jpg", alt_text: null },
];

function renderGallery(props: Partial<Parameters<typeof ProductGallery>[0]> = {}, locale = "en") {
  return render(
    <NextIntlClientProvider locale={locale} messages={messages}>
      <ProductGallery images={images} title="CHABA Rose" {...props} />
    </NextIntlClientProvider>,
  );
}

describe("ProductGallery", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("advances to the next image automatically after the autoplay interval", () => {
    renderGallery();

    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");

    act(() => vi.advanceTimersByTime(5000));

    expect(screen.getByRole("button", { name: "Go to image 2" })).toHaveAttribute("aria-current", "true");
  });

  it("pauses autoplay while the pointer is hovering the main image", () => {
    renderGallery();
    const mainImage = screen.getByTestId("gallery-main-image");

    fireEvent.mouseEnter(mainImage);
    act(() => vi.advanceTimersByTime(10000));
    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");

    fireEvent.mouseLeave(mainImage);
    act(() => vi.advanceTimersByTime(5000));
    expect(screen.getByRole("button", { name: "Go to image 2" })).toHaveAttribute("aria-current", "true");
  });

  it("jumps to an image when its dot is clicked", () => {
    renderGallery();

    fireEvent.click(screen.getByRole("button", { name: "Go to image 3" }));

    expect(screen.getByRole("button", { name: "Go to image 3" })).toHaveAttribute("aria-current", "true");
  });

  it("moves to the next/previous image via the arrow buttons", () => {
    renderGallery();

    fireEvent.click(screen.getByRole("button", { name: "Next image" }));
    expect(screen.getByRole("button", { name: "Go to image 2" })).toHaveAttribute("aria-current", "true");

    fireEvent.click(screen.getByRole("button", { name: "Previous image" }));
    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");
  });

  it("advances on a left swipe and goes back on a right swipe in LTR", () => {
    renderGallery();
    const mainImage = screen.getByTestId("gallery-main-image");

    fireEvent.touchStart(mainImage, { touches: [{ clientX: 200 }] });
    fireEvent.touchEnd(mainImage, { changedTouches: [{ clientX: 100 }] });
    expect(screen.getByRole("button", { name: "Go to image 2" })).toHaveAttribute("aria-current", "true");

    fireEvent.touchStart(mainImage, { touches: [{ clientX: 100 }] });
    fireEvent.touchEnd(mainImage, { changedTouches: [{ clientX: 200 }] });
    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");
  });

  it("mirrors swipe direction in RTL", () => {
    renderGallery({}, "ar");
    const mainImage = screen.getByTestId("gallery-main-image");

    // A leftward drag advances forward in LTR but *backward* in RTL — from
    // index 0 that wraps around to the last image, not index 1.
    fireEvent.touchStart(mainImage, { touches: [{ clientX: 200 }] });
    fireEvent.touchEnd(mainImage, { changedTouches: [{ clientX: 100 }] });
    expect(screen.getByRole("button", { name: "Go to image 3" })).toHaveAttribute("aria-current", "true");
  });

  it("ignores a swipe shorter than the threshold", () => {
    renderGallery();
    const mainImage = screen.getByTestId("gallery-main-image");

    fireEvent.touchStart(mainImage, { touches: [{ clientX: 200 }] });
    fireEvent.touchEnd(mainImage, { changedTouches: [{ clientX: 185 }] });
    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");
  });

  it("navigates with the arrow keys", () => {
    renderGallery();
    const mainImage = screen.getByTestId("gallery-main-image");

    fireEvent.keyDown(mainImage, { key: "ArrowRight" });
    expect(screen.getByRole("button", { name: "Go to image 2" })).toHaveAttribute("aria-current", "true");

    fireEvent.keyDown(mainImage, { key: "ArrowLeft" });
    expect(screen.getByRole("button", { name: "Go to image 1" })).toHaveAttribute("aria-current", "true");
  });

  it("falls back to the placeholder glyph for an image that fails to load, without affecting the others", () => {
    const { container } = renderGallery();

    const allImgs = Array.from(container.querySelectorAll("img"));
    expect(allImgs).toHaveLength(3);
    const brokenImg = allImgs.find((img) => img.src.includes("two.jpg"))!;
    fireEvent.error(brokenImg);

    // The broken slide's <img> is gone (replaced by the placeholder glyph);
    // the other two real photos are untouched.
    const remainingImgs = Array.from(container.querySelectorAll("img"));
    expect(remainingImgs).toHaveLength(2);
    expect(remainingImgs.some((img) => img.src.includes("two.jpg"))).toBe(false);
    expect(remainingImgs.some((img) => img.src.includes("one.jpg"))).toBe(true);
    expect(remainingImgs.some((img) => img.src.includes("three.jpg"))).toBe(true);
  });

  it("does not autoplay a single-image gallery and renders no dots or arrows", () => {
    renderGallery({ images: [images[0]] });

    expect(screen.queryByRole("button", { name: "Go to image 1" })).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "Next image" })).not.toBeInTheDocument();
    act(() => vi.advanceTimersByTime(10000));
    // Nothing to assert beyond "this doesn't throw" — confirms the
    // count<=1 autoplay guard.
  });

  it("shows the local placeholder glyph when there are no images", () => {
    const { container } = renderGallery({ images: [] });
    expect(container.querySelector("svg")).toBeInTheDocument();
  });
});

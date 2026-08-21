import { describe, expect, it, vi, afterEach, beforeEach } from "vitest";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import type { ComponentProps } from "react";
import { HeroSlider } from "./HeroSlider";
import type { HeroSlide } from "@/lib/api";

// next-intl's Link (@/i18n/navigation) pulls in next/navigation, which
// doesn't resolve under Vitest's plain jsdom environment (no Next.js app
// router harness) — no other test in this repo renders a Link-using
// component yet, so this is a net-new, narrowly-scoped mock rather than an
// existing convention to follow.
vi.mock("@/i18n/navigation", () => ({
  Link: ({ href, children, ...rest }: ComponentProps<"a"> & { href: string }) => (
    <a href={href} {...rest}>
      {children}
    </a>
  ),
}));

const messages = {
  Home: { title: "CHABA" },
  Hero: {
    heroProductAriaLabel: "Shop the featured product",
    prevSlide: "Previous slide",
    nextSlide: "Next slide",
    goToSlide: "Go to slide {number}",
    pauseSlideshow: "Pause slideshow",
    playSlideshow: "Play slideshow",
  },
};

const slides: HeroSlide[] = [
  {
    id: "1",
    product: { slug: "rose-perfume", name: { ar: "عطر الورد", fr: "Parfum", en: "Rose Perfume" } },
    image_url: "/rose.jpg",
    mobile_image_url: null,
    title: { ar: "عطر الورد", fr: "Parfum de Rose", en: "Rose Perfume" },
    subtitle: { ar: null, fr: null, en: null },
    cta_label: { ar: "تسوّق", fr: "Acheter", en: "Shop Now" },
  },
  {
    id: "2",
    product: { slug: "oud-perfume", name: { ar: "عطر العود", fr: "Oud", en: "Oud Perfume" } },
    image_url: "/oud.jpg",
    mobile_image_url: null,
    title: { ar: "عطر العود", fr: "Oud", en: "Oud Perfume" },
    subtitle: { ar: null, fr: null, en: null },
    cta_label: { ar: "تسوّق", fr: "Acheter", en: "Shop Now" },
  },
];

function renderSlider() {
  return render(
    <NextIntlClientProvider locale="en" messages={messages}>
      <HeroSlider slides={slides} />
    </NextIntlClientProvider>,
  );
}

describe("HeroSlider", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("advances to the next slide automatically after the autoplay interval", () => {
    renderSlider();

    expect(screen.getByRole("heading", { name: "Rose Perfume" })).toBeInTheDocument();

    act(() => vi.advanceTimersByTime(5000));

    expect(screen.getByRole("heading", { name: "Oud Perfume" })).toBeInTheDocument();
  });

  it("pauses autoplay while the pointer is hovering the slider", () => {
    renderSlider();
    const section = screen.getByRole("heading", { name: "Rose Perfume" }).closest("section")!;

    fireEvent.mouseEnter(section);
    act(() => vi.advanceTimersByTime(10000));

    expect(screen.getByRole("heading", { name: "Rose Perfume" })).toBeInTheDocument();

    fireEvent.mouseLeave(section);
    act(() => vi.advanceTimersByTime(5000));

    expect(screen.getByRole("heading", { name: "Oud Perfume" })).toBeInTheDocument();
  });

  it("jumps to a slide when its dot is clicked, and stops autoplay when manually paused", () => {
    renderSlider();

    fireEvent.click(screen.getByRole("button", { name: "Go to slide 2" }));
    expect(screen.getByRole("heading", { name: "Oud Perfume" })).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Pause slideshow" }));
    act(() => vi.advanceTimersByTime(10000));
    expect(screen.getByRole("heading", { name: "Oud Perfume" })).toBeInTheDocument();
  });
});

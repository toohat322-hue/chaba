import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { act, render } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { ShabaLoaderOverlay } from "./ShabaLoaderOverlay";

let mockPathname = "/ar";
let mockSearchParams = new URLSearchParams();

vi.mock("@/i18n/navigation", () => ({
  usePathname: () => mockPathname,
}));

vi.mock("next/navigation", () => ({
  useSearchParams: () => mockSearchParams,
}));

const messages = { Loading: { label: "جارٍ التحميل...", labelBase: "جارٍ التحميل" } };

function renderOverlay() {
  return render(
    <NextIntlClientProvider locale="ar" messages={messages}>
      <ShabaLoaderOverlay />
    </NextIntlClientProvider>,
  );
}

function isVisible(container: HTMLElement) {
  return container.querySelector(".shaba-loader-overlay")?.classList.contains("is-visible") ?? false;
}

// Keeps window.location in sync with whatever the test says the "current
// page" is — handlePopState reads window.location directly (it can't use
// the mocked hooks, which is exactly the real component's behavior too).
function setLocation(pathname: string, search = "") {
  window.history.pushState({}, "", pathname + search);
}

describe("ShabaLoaderOverlay", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockPathname = "/ar";
    mockSearchParams = new URLSearchParams();
    setLocation("/ar");
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("shows then hides on a normal navigation (pathname actually changes)", () => {
    const { container, rerender } = renderOverlay();

    setLocation("/ar/products/foo");
    window.dispatchEvent(new PopStateEvent("popstate"));

    act(() => vi.advanceTimersByTime(150)); // SHOW_DELAY_MS
    expect(isVisible(container)).toBe(true);

    // The destination "commits" — pathname prop changes, component re-renders.
    mockPathname = "/products/foo";
    rerender(
      <NextIntlClientProvider locale="ar" messages={messages}>
        <ShabaLoaderOverlay />
      </NextIntlClientProvider>,
    );

    act(() => vi.advanceTimersByTime(300)); // MIN_DISPLAY_MS
    expect(isVisible(container)).toBe(false);
  });

  it("does not start the overlay for a popstate that nets out to the same URL", () => {
    const { container } = renderOverlay();

    // Simulate a fast Back-then-Forward: window.location ends up back where
    // it already was, even though a popstate fires.
    window.dispatchEvent(new PopStateEvent("popstate"));

    act(() => vi.advanceTimersByTime(150));
    expect(isVisible(container)).toBe(false);

    act(() => vi.advanceTimersByTime(10000));
    expect(isVisible(container)).toBe(false);
  });

  it("force-closes via the safety net if the URL never actually changes after a real popstate", () => {
    const { container } = renderOverlay();

    setLocation("/ar/products/foo");
    window.dispatchEvent(new PopStateEvent("popstate"));

    act(() => vi.advanceTimersByTime(150));
    expect(isVisible(container)).toBe(true);

    // pathname never changes (simulating a navigation that errors out or
    // otherwise never commits) — without the safety net this stays stuck.
    act(() => vi.advanceTimersByTime(8000));
    expect(isVisible(container)).toBe(false);
  });

  it("does not show the overlay at all for a fast navigation resolved before SHOW_DELAY_MS", () => {
    const { container, rerender } = renderOverlay();

    setLocation("/ar/products/foo");
    window.dispatchEvent(new PopStateEvent("popstate"));

    mockPathname = "/products/foo";
    rerender(
      <NextIntlClientProvider locale="ar" messages={messages}>
        <ShabaLoaderOverlay />
      </NextIntlClientProvider>,
    );

    act(() => vi.advanceTimersByTime(150));
    expect(isVisible(container)).toBe(false);
  });
});

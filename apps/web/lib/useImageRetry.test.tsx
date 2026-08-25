import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { act, render } from "@testing-library/react";
import { useImageRetry } from "./useImageRetry";

function TestHarness({ onRender }: { onRender: (result: ReturnType<typeof useImageRetry>) => void }) {
  const result = useImageRetry();
  onRender(result);
  return null;
}

describe("useImageRetry", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("retries twice with a delay before falling back", () => {
    let latest: ReturnType<typeof useImageRetry>;
    render(<TestHarness onRender={(r) => (latest = r)} />);

    expect(latest!.key).toBe(0);
    expect(latest!.failed).toBe(false);

    // First error: schedules a delayed retry, doesn't fail immediately and
    // doesn't bump the key until the delay elapses.
    act(() => latest!.onError());
    expect(latest!.key).toBe(0);
    expect(latest!.failed).toBe(false);

    act(() => vi.advanceTimersByTime(400));
    expect(latest!.key).toBe(1);
    expect(latest!.failed).toBe(false);

    // Second error: same pattern, second delay.
    act(() => latest!.onError());
    act(() => vi.advanceTimersByTime(900));
    expect(latest!.key).toBe(2);
    expect(latest!.failed).toBe(false);

    // Third error: retries exhausted, fails immediately (no more delay).
    act(() => latest!.onError());
    expect(latest!.failed).toBe(true);
    expect(latest!.key).toBe(2);
  });

  it("clears a pending retry timer on unmount without throwing", () => {
    let latest: ReturnType<typeof useImageRetry>;
    const { unmount } = render(<TestHarness onRender={(r) => (latest = r)} />);

    act(() => latest!.onError());
    unmount();

    expect(() => act(() => vi.advanceTimersByTime(2000))).not.toThrow();
  });
});

"use client";

import { useCallback, useState } from "react";

// A transient failure (a dropped connection, a slow edge node) shouldn't
// need a full page refresh to recover from — but retrying forever on a
// genuinely broken URL is just as wrong. Bounded retry: a couple of quick
// attempts, then settle into the caller's fallback for good.
const MAX_RETRIES = 2;

/**
 * Shared retry-then-fallback behavior for a single <img>/<Image> element.
 * `key` must be spread onto the element (forces a real remount — and so an
 * actual fresh network request — on each retry, since changing `src` back
 * to the same value is a no-op); `onError` goes on the element's onError;
 * `failed` tells the caller to render its own fallback once retries are
 * exhausted.
 */
export function useImageRetry() {
  const [attempt, setAttempt] = useState(0);
  const [failed, setFailed] = useState(false);

  const onError = useCallback(() => {
    setAttempt((current) => {
      if (current >= MAX_RETRIES) {
        setFailed(true);
        return current;
      }
      return current + 1;
    });
  }, []);

  return { key: attempt, failed, onError };
}

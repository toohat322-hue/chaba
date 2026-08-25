"use client";

import { useCallback, useEffect, useRef, useState } from "react";

// A transient failure (a dropped connection, a slow edge node) shouldn't
// need a full page refresh to recover from — but retrying forever on a
// genuinely broken URL is just as wrong. Bounded retry: a couple of quick
// attempts, then settle into the caller's fallback for good.
const MAX_RETRIES = 2;
// A short, increasing delay before each retry — an *immediate* remount
// re-requests the exact same URL over what may well be the exact same
// connection/edge node that just failed, so it mostly just fails the same
// way again instantly. A brief pause gives a real chance of a different
// outcome (a fresh DNS/TLS handshake, a different CDN edge) without making
// a genuinely broken image noticeably slower to fall back.
const RETRY_DELAY_MS = [400, 900];

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
  const retryCountRef = useRef(0);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, []);

  const onError = useCallback(() => {
    if (retryCountRef.current >= MAX_RETRIES) {
      setFailed(true);
      return;
    }

    const delay = RETRY_DELAY_MS[retryCountRef.current] ?? RETRY_DELAY_MS[RETRY_DELAY_MS.length - 1];
    retryCountRef.current += 1;
    timerRef.current = setTimeout(() => {
      timerRef.current = null;
      setAttempt((current) => current + 1);
    }, delay);
  }, []);

  return { key: attempt, failed, onError };
}

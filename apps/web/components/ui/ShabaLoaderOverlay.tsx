"use client";

import { useEffect, useRef, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { usePathname } from "@/i18n/navigation";
import { ShabaLoader } from "./ShabaLoader";

// Don't render the overlay at all if navigation resolves before this many ms
// have passed since the click — avoids a flash-then-gone loader on fast
// transitions.
const SHOW_DELAY_MS = 150;
// Once the overlay has actually become visible, keep it up for at least
// this long even if navigation finishes sooner — avoids a jarring
// blink-and-vanish on borderline-fast transitions.
const MIN_DISPLAY_MS = 300;

/**
 * Global route-transition overlay — an always-mounted `fixed inset-0`
 * layer (`.shaba-loader-overlay` in globals.css) that fades in/out over
 * whatever page is currently on screen, rather than Next's `loading.tsx`
 * Suspense convention (which unmounts the old page and replaces it with the
 * fallback — the "separate page" behavior this replaces). Tracks
 * navigation itself: a page-to-page click starts the pending timer, and the
 * URL actually changing (pathname or search params) ends it.
 */
export function ShabaLoaderOverlay() {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const t = useTranslations("Loading");

  const [visible, setVisible] = useState(false);

  const pendingRef = useRef(false);
  const shownAtRef = useRef<number | null>(null);
  const showTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const currentKeyRef = useRef(`${pathname}?${searchParams.toString()}`);

  useEffect(() => {
    function clearTimers() {
      if (showTimerRef.current) clearTimeout(showTimerRef.current);
      if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
      showTimerRef.current = null;
      hideTimerRef.current = null;
    }

    function start() {
      if (pendingRef.current) return;
      pendingRef.current = true;
      clearTimers();
      showTimerRef.current = setTimeout(() => {
        showTimerRef.current = null;
        shownAtRef.current = Date.now();
        setVisible(true);
      }, SHOW_DELAY_MS);
    }

    function handleClick(event: MouseEvent) {
      if (event.defaultPrevented || event.button !== 0) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

      const target = event.target as HTMLElement | null;
      const anchor = target?.closest("a");
      if (!anchor || !anchor.href) return;
      if (anchor.target && anchor.target !== "_self") return;
      if (anchor.hasAttribute("download")) return;

      let url: URL;
      try {
        url = new URL(anchor.href, window.location.href);
      } catch {
        return;
      }
      if (url.origin !== window.location.origin) return;

      const isSameUrl = url.pathname === window.location.pathname && url.search === window.location.search;
      if (isSameUrl) return;

      start();
    }

    function handlePopState() {
      start();
    }

    document.addEventListener("click", handleClick, { capture: true });
    window.addEventListener("popstate", handlePopState);
    // A click on an <a> (above) or back/forward (popstate) covers nearly
    // every real navigation, but a handful of controls navigate
    // programmatically from a non-anchor element (e.g. SortSelect's <select
    // onChange> calling router.push()) — those dispatch this instead of
    // being caught by the click listener.
    window.addEventListener("shaba:navigation-start", start);
    return () => {
      document.removeEventListener("click", handleClick, { capture: true });
      window.removeEventListener("popstate", handlePopState);
      window.removeEventListener("shaba:navigation-start", start);
      clearTimers();
    };
  }, []);

  // "End": the destination has actually committed — pathname or search
  // params changed relative to what we last saw.
  useEffect(() => {
    const key = `${pathname}?${searchParams.toString()}`;
    if (key === currentKeyRef.current) return;
    currentKeyRef.current = key;

    if (!pendingRef.current) return;
    pendingRef.current = false;

    if (showTimerRef.current) {
      // Resolved before it ever became visible — nothing to fade out.
      clearTimeout(showTimerRef.current);
      showTimerRef.current = null;
      return;
    }

    const elapsed = shownAtRef.current ? Date.now() - shownAtRef.current : MIN_DISPLAY_MS;
    const remaining = Math.max(0, MIN_DISPLAY_MS - elapsed);
    hideTimerRef.current = setTimeout(() => setVisible(false), remaining);
  }, [pathname, searchParams]);

  return (
    <div className={`shaba-loader-overlay ${visible ? "is-visible" : ""}`} aria-hidden={!visible}>
      <ShabaLoader label={t("label")} labelBase={t("labelBase")} showIndicator />
    </div>
  );
}

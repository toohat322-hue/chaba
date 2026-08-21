"use client";

import { useEffect, useRef, useState, type FormEvent } from "react";
import { createPortal } from "react-dom";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";

function SearchIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5 shrink-0">
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-4.3-4.3" strokeLinecap="round" />
    </svg>
  );
}

function CloseIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
    </svg>
  );
}

export function SearchOverlay({ open, onClose }: { open: boolean; onClose: () => void }) {
  const t = useTranslations("Nav");
  const router = useRouter();
  const [query, setQuery] = useState("");
  const inputRef = useRef<HTMLInputElement>(null);
  // Same server/client portal mismatch as components/ui/Drawer.tsx — gate on
  // a post-mount flag rather than `typeof document`.
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- one-time post-mount flag for SSR-safe portal rendering, not state derived from props/render
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    // Autofocus needs the panel's slide-in transition to have started so the
    // input is actually in the layout/visible when focus lands.
    const focusTimer = setTimeout(() => inputRef.current?.focus(), 50);

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose();
    }
    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
      clearTimeout(focusTimer);
    };
  }, [open, onClose]);

  function handleSubmit(event: FormEvent) {
    event.preventDefault();
    const trimmed = query.trim();
    if (!trimmed) return;
    router.push(`/search?q=${encodeURIComponent(trimmed)}`);
    onClose();
  }

  if (!mounted) return null;

  return createPortal(
    <div className={`fixed inset-0 z-100 ${open ? "" : "pointer-events-none"}`}>
      <div
        onClick={onClose}
        aria-hidden="true"
        className={`absolute inset-0 bg-ink/40 transition-opacity duration-300 motion-reduce:transition-none ${
          open ? "opacity-100" : "opacity-0"
        }`}
      />
      <div
        role="dialog"
        aria-modal="true"
        aria-label={t("searchSubmit")}
        aria-hidden={!open}
        className={`search-overlay-panel absolute inset-x-0 top-0 border-b border-primary/10 bg-background shadow-lift ${
          open ? "is-open" : ""
        }`}
      >
        <form onSubmit={handleSubmit} className="mx-auto flex max-w-3xl items-center gap-3 px-4 py-5 sm:px-6 sm:py-7">
          <SearchIcon />
          <input
            ref={inputRef}
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={t("searchPlaceholder")}
            className="min-w-0 flex-1 bg-transparent text-h3 text-ink placeholder:text-ink/35 focus:outline-none"
            enterKeyHint="search"
          />
          <button
            type="submit"
            className="hidden shrink-0 rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50 sm:block"
          >
            {t("searchSubmit")}
          </button>
          <button
            type="button"
            onClick={onClose}
            aria-label={t("closeSearch")}
            className="shrink-0 rounded-full p-2 text-ink/60 transition-colors hover:bg-primary/5 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
          >
            <CloseIcon />
          </button>
        </form>
      </div>
    </div>,
    document.body,
  );
}

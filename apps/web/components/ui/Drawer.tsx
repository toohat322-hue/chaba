"use client";

import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";

function CloseIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" className="h-5 w-5">
      <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
    </svg>
  );
}

export function Drawer({
  open,
  onClose,
  title,
  closeLabel,
  children,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  closeLabel: string;
  children: React.ReactNode;
}) {
  const panelRef = useRef<HTMLDivElement>(null);
  // Server always renders nothing for a portal (no `document` to portal
  // into); rendering the portal on the client's very first pass — before
  // this mount effect flips `mounted` — would render *something* where the
  // server rendered nothing, which is a hydration mismatch. Gating on a
  // post-mount flag keeps the client's first render identical to the
  // server's, then reveals the portal one tick later.
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- one-time post-mount flag for SSR-safe portal rendering, not state derived from props/render
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    panelRef.current?.focus();

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose();
    }
    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open, onClose]);

  if (!mounted) return null;

  return createPortal(
    <div className={`fixed inset-0 z-100 ${open ? "" : "pointer-events-none"}`}>
      <div
        onClick={onClose}
        aria-hidden="true"
        className={`absolute inset-0 bg-ink/40 transition-opacity duration-300 ${
          open ? "opacity-100" : "opacity-0"
        }`}
      />
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        aria-hidden={!open}
        tabIndex={-1}
        className={`drawer-panel absolute inset-y-0 end-0 flex h-full w-full max-w-md flex-col bg-background shadow-lift outline-none ${
          open ? "is-open" : ""
        }`}
      >
        <div className="flex items-center justify-between border-b border-primary/10 px-6 py-4">
          <h2 className="text-h3 font-semibold text-ink">{title}</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={closeLabel}
            className="rounded-full p-2 text-ink/60 transition-colors hover:bg-primary/5 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
          >
            <CloseIcon />
          </button>
        </div>
        <div className="flex-1 overflow-y-auto">{children}</div>
      </div>
    </div>,
    document.body,
  );
}

"use client";

import { createContext, useCallback, useContext, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { useTranslations } from "next-intl";

type ToastEntry = {
  id: number;
  message: string;
  actionLabel?: string;
  onAction?: () => void;
};

type ToastContextValue = {
  show: (toast: Omit<ToastEntry, "id">) => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);
const DISMISS_MS = 4000;

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const t = useTranslations("Toast");
  const [toasts, setToasts] = useState<ToastEntry[]>([]);
  const idRef = useRef(0);
  const timers = useRef(new Map<number, ReturnType<typeof setTimeout>>());
  // Same server/client portal mismatch as components/ui/Drawer.tsx — gate on
  // a post-mount flag rather than `typeof document`, which is already
  // defined by the client's first (pre-hydration-settled) render.
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- one-time post-mount flag for SSR-safe portal rendering, not state derived from props/render
    setMounted(true);
  }, []);

  const dismiss = useCallback((id: number) => {
    setToasts((current) => current.filter((toast) => toast.id !== id));
    const timer = timers.current.get(id);
    if (timer) {
      clearTimeout(timer);
      timers.current.delete(id);
    }
  }, []);

  const show = useCallback(
    (toast: Omit<ToastEntry, "id">) => {
      const id = idRef.current++;
      setToasts((current) => [...current, { ...toast, id }]);
      timers.current.set(
        id,
        setTimeout(() => dismiss(id), DISMISS_MS),
      );
    },
    [dismiss],
  );

  function pause(id: number) {
    const timer = timers.current.get(id);
    if (timer) clearTimeout(timer);
  }

  function resume(id: number) {
    timers.current.set(
      id,
      setTimeout(() => dismiss(id), DISMISS_MS),
    );
  }

  return (
    <ToastContext.Provider value={{ show }}>
      {children}
      {mounted &&
        createPortal(
          <div
            role="status"
            aria-live="polite"
            // bottom-20 on mobile clears the fixed sticky add-to-cart bar
            // (VariantSelector.tsx) that only exists below `sm:` — without
            // it the toast (z-200) fully covers that bar (z-40) for its
            // whole 4s lifetime, hiding the Add to Cart button right after
            // the user taps it.
            className="pointer-events-none fixed inset-x-0 bottom-20 z-200 flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:bottom-4 sm:end-4 sm:items-end"
          >
            {toasts.map((toast) => (
              <div
                key={toast.id}
                onMouseEnter={() => pause(toast.id)}
                onMouseLeave={() => resume(toast.id)}
                className="animate-toast-in pointer-events-auto flex w-full max-w-sm items-center justify-between gap-4 rounded-2xl bg-ink px-5 py-3.5 text-background shadow-lift"
              >
                <span className="text-small font-medium">{toast.message}</span>
                <div className="flex shrink-0 items-center gap-3">
                  {toast.actionLabel && toast.onAction && (
                    <button
                      type="button"
                      onClick={() => {
                        toast.onAction?.();
                        dismiss(toast.id);
                      }}
                      className="rounded text-small font-semibold text-accent hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
                    >
                      {toast.actionLabel}
                    </button>
                  )}
                  <button
                    type="button"
                    onClick={() => dismiss(toast.id)}
                    aria-label={t("dismiss")}
                    className="rounded text-background/50 transition-colors hover:text-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
                  >
                    ×
                  </button>
                </div>
              </div>
            ))}
          </div>,
          document.body,
        )}
    </ToastContext.Provider>
  );
}

export function useToast(): ToastContextValue {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error("useToast must be used within a ToastProvider");
  }
  return context;
}

"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { addToWishlist, getWishlist, removeFromWishlist, type WishlistItem } from "@/lib/api";

type WishlistContextValue = {
  items: WishlistItem[];
  loading: boolean;
  has: (productId: string) => boolean;
  add: (productId: string) => Promise<void>;
  remove: (productId: string) => Promise<void>;
  toggle: (productId: string) => Promise<void>;
};

const WishlistContext = createContext<WishlistContextValue | null>(null);

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getWishlist()
      .then(setItems)
      .catch((error) => console.error("Failed to load wishlist", error))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    // Same identity-changed signal CartProvider listens for (see its
    // comment) — without this, a login/logout doesn't reload the wishlist
    // either, so it keeps showing the previous identity's items.
    function onIdentityChanged() {
      getWishlist()
        .then(setItems)
        .catch((error) => console.error("Failed to reload wishlist", error));
    }

    window.addEventListener("chaba:cart-updated", onIdentityChanged);

    return () => window.removeEventListener("chaba:cart-updated", onIdentityChanged);
  }, []);

  const has = useCallback((productId: string) => items.some((item) => item.product_id === productId), [items]);

  const add = useCallback(async (productId: string) => {
    const item = await addToWishlist(productId);
    setItems((current) => (current.some((i) => i.product_id === productId) ? current : [item, ...current]));
  }, []);

  const remove = useCallback(async (productId: string) => {
    await removeFromWishlist(productId);
    setItems((current) => current.filter((item) => item.product_id !== productId));
  }, []);

  const toggle = useCallback(
    async (productId: string) => {
      if (has(productId)) {
        await remove(productId);
      } else {
        await add(productId);
      }
    },
    [has, add, remove],
  );

  const value = useMemo(() => ({ items, loading, has, add, remove, toggle }), [items, loading, has, add, remove, toggle]);

  return <WishlistContext.Provider value={value}>{children}</WishlistContext.Provider>;
}

export function useWishlist(): WishlistContextValue {
  const context = useContext(WishlistContext);
  if (!context) {
    throw new Error("useWishlist must be used within a WishlistProvider");
  }
  return context;
}

"use client";

import { createContext, useCallback, useContext, useEffect, useState } from "react";
import {
  ApiError,
  addCartItem,
  applyCoupon as applyCouponRequest,
  getCart,
  removeCartItem,
  removeCoupon as removeCouponRequest,
  updateCartItem,
  type CartData,
} from "@/lib/api";

type CartContextValue = {
  cart: CartData | null;
  loading: boolean;
  addItem: (variantId: string, quantity: number) => Promise<void>;
  updateItem: (itemId: string, quantity: number) => Promise<void>;
  removeItem: (itemId: string) => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => Promise<void>;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [cart, setCart] = useState<CartData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getCart()
      .then(setCart)
      .catch((error) => console.error("Failed to load cart", error))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    // CustomerAuthProvider sits above this provider in the tree (so it
    // can't call useCart() directly) and dispatches this after merging a
    // guest cart into the newly-authenticated account's own cart — a plain
    // DOM event, same cross-component-signal convention already used for
    // ShabaLoaderOverlay's "shaba:navigation-start".
    function onCartUpdated() {
      getCart()
        .then(setCart)
        .catch((error) => console.error("Failed to reload cart", error));
    }

    window.addEventListener("chaba:cart-updated", onCartUpdated);

    return () => window.removeEventListener("chaba:cart-updated", onCartUpdated);
  }, []);

  const addItem = useCallback(async (variantId: string, quantity: number) => {
    const updated = await addCartItem(variantId, quantity);
    setCart(updated);
  }, []);

  const updateItem = useCallback(async (itemId: string, quantity: number) => {
    const updated = await updateCartItem(itemId, quantity);
    setCart(updated);
  }, []);

  const removeItem = useCallback(async (itemId: string) => {
    const updated = await removeCartItem(itemId);
    setCart(updated);
  }, []);

  const applyCoupon = useCallback(async (code: string) => {
    const updated = await applyCouponRequest(code);
    setCart(updated);
  }, []);

  const removeCoupon = useCallback(async () => {
    const updated = await removeCouponRequest();
    setCart(updated);
  }, []);

  return (
    <CartContext.Provider value={{ cart, loading, addItem, updateItem, removeItem, applyCoupon, removeCoupon }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart(): CartContextValue {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error("useCart must be used within a CartProvider");
  }
  return context;
}

export { ApiError };

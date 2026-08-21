"use client";

import { createContext, useContext, useEffect, useState } from "react";
import {
  customerLogin,
  customerLogout,
  customerRegister,
  mergeGuestCart,
  type CustomerUser,
} from "@/lib/api";
import { getCustomerTokens, clearCustomerTokens } from "@/lib/customer-session";
import { peekSessionToken } from "@/lib/cart-session";

type RegisterPayload = {
  full_name: string;
  phone: string;
  email?: string;
  password: string;
  password_confirmation: string;
};

/** Thrown by login()/register() when the account has 2FA enabled — the
 * caller must complete verification (see lib/api.ts customerVerifyTwoFactor)
 * and then call setSession() itself, since no tokens exist yet at this point. */
export class TwoFactorRequiredError extends Error {
  constructor(public readonly twoFactorToken: string) {
    super("two_factor_required");
  }
}

type CustomerAuthContextValue = {
  customer: CustomerUser | null;
  loading: boolean;
  login: (login: string, password: string) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<void>;
  /** Persists an already-issued session (tokens are already stored by
   * whichever lib/api.ts call produced `user` — customerLogin,
   * customerRegister, customerVerifyTwoFactor, or exchangeSocialCode) and
   * folds in any pending guest cart. The one place all four session-
   * establishing flows converge, so cart-merge only needs wiring once. */
  setSession: (user: CustomerUser) => Promise<void>;
};

const CustomerAuthContext = createContext<CustomerAuthContextValue | null>(null);

export function CustomerAuthProvider({ children }: { children: React.ReactNode }) {
  const [customer, setCustomer] = useState<CustomerUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // No GET /customer/me endpoint is used here — the identity we have is
    // whatever /auth/login or /auth/register last returned, persisted
    // alongside the tokens (same approach as AdminAuthProvider).
    const { access } = getCustomerTokens();
    if (access) {
      const stored = window.sessionStorage.getItem("chaba_customer_user");
      if (stored) {
        // eslint-disable-next-line react-hooks/set-state-in-effect -- client-only session hydration, must run after mount (window/sessionStorage don't exist during SSR)
        setCustomer(JSON.parse(stored));
      }
    }
    setLoading(false);
  }, []);

  async function setSession(user: CustomerUser) {
    setCustomer(user);
    window.sessionStorage.setItem("chaba_customer_user", JSON.stringify(user));

    const guestToken = peekSessionToken();
    if (guestToken) {
      try {
        await mergeGuestCart(guestToken);
        window.dispatchEvent(new Event("chaba:cart-updated"));
      } catch {
        // A merge problem must never block login/register/social-login
        // itself — the user is still authenticated either way.
      }
    }
  }

  async function login(loginValue: string, password: string) {
    const data = await customerLogin(loginValue, password);
    if (data.requires_two_factor) {
      throw new TwoFactorRequiredError(data.two_factor_token);
    }
    await setSession(data.user);
  }

  async function register(payload: RegisterPayload) {
    const data = await customerRegister(payload);
    if (data.requires_two_factor) {
      throw new TwoFactorRequiredError(data.two_factor_token);
    }
    await setSession(data.user);
  }

  async function logout() {
    await customerLogout();
    setCustomer(null);
    window.sessionStorage.removeItem("chaba_customer_user");
    clearCustomerTokens();

    // CartProvider only ever reloads on this event (see its effect) or on
    // mount — without dispatching it here, the cart drawer/page keeps
    // showing this account's cart after logout (tokens are cleared, but
    // the in-memory `cart` state isn't), and on a shared device the next
    // guest/different customer could act on line items that are no longer
    // theirs.
    window.dispatchEvent(new Event("chaba:cart-updated"));
  }

  return (
    <CustomerAuthContext.Provider value={{ customer, loading, login, register, logout, setSession }}>
      {children}
    </CustomerAuthContext.Provider>
  );
}

export function useCustomerAuth(): CustomerAuthContextValue {
  const context = useContext(CustomerAuthContext);
  if (!context) {
    throw new Error("useCustomerAuth must be used within a CustomerAuthProvider");
  }
  return context;
}

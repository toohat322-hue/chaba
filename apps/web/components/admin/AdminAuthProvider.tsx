"use client";

import { createContext, useContext, useEffect, useState } from "react";
import { adminLogin, adminLogout, adminVerifyTwoFactor, type AdminUser } from "@/lib/api";
import { getAdminTokens, clearAdminTokens } from "@/lib/admin-session";

type LoginResult = { requiresTwoFactor: false } | { requiresTwoFactor: true; twoFactorToken: string };

type AdminAuthContextValue = {
  admin: AdminUser | null;
  loading: boolean;
  login: (login: string, password: string) => Promise<LoginResult>;
  verifyTwoFactor: (twoFactorToken: string, code: string) => Promise<void>;
  logout: () => Promise<void>;
};

const AdminAuthContext = createContext<AdminAuthContextValue | null>(null);

export function AdminAuthProvider({ children }: { children: React.ReactNode }) {
  const [admin, setAdmin] = useState<AdminUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Avoids an extra round trip on every page load — the admin identity
    // we have is whatever /auth/login (or /auth/2fa/verify) last returned,
    // persisted alongside the tokens rather than re-fetched via /users/me.
    const { access } = getAdminTokens();
    if (access) {
      const stored = window.sessionStorage.getItem("chaba_admin_user");
      if (stored) {
        // eslint-disable-next-line react-hooks/set-state-in-effect -- client-only session hydration, must run after mount (window/sessionStorage don't exist during SSR)
        setAdmin(JSON.parse(stored));
      }
    }
    setLoading(false);
  }, []);

  async function login(loginValue: string, password: string): Promise<LoginResult> {
    const data = await adminLogin(loginValue, password);

    if (data.requires_two_factor) {
      return { requiresTwoFactor: true, twoFactorToken: data.two_factor_token };
    }

    setAdmin(data.user);
    window.sessionStorage.setItem("chaba_admin_user", JSON.stringify(data.user));

    return { requiresTwoFactor: false };
  }

  async function verifyTwoFactor(twoFactorToken: string, code: string) {
    const data = await adminVerifyTwoFactor(twoFactorToken, code);
    setAdmin(data.user);
    window.sessionStorage.setItem("chaba_admin_user", JSON.stringify(data.user));
  }

  async function logout() {
    await adminLogout();
    setAdmin(null);
    window.sessionStorage.removeItem("chaba_admin_user");
    clearAdminTokens();
  }

  return (
    <AdminAuthContext.Provider value={{ admin, loading, login, verifyTwoFactor, logout }}>
      {children}
    </AdminAuthContext.Provider>
  );
}

export function useAdminAuth(): AdminAuthContextValue {
  const context = useContext(AdminAuthContext);
  if (!context) {
    throw new Error("useAdminAuth must be used within an AdminAuthProvider");
  }
  return context;
}

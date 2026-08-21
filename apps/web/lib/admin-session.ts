"use client";

const ACCESS_KEY = "chaba_admin_access_token";
const REFRESH_KEY = "chaba_admin_refresh_token";

/**
 * Admin token storage — mirrors lib/cart-session.ts's localStorage pattern
 * for consistency with the rest of this app, rather than introducing a new
 * persistence approach just for admin.
 */
export function getAdminTokens(): { access: string | null; refresh: string | null } {
  if (typeof window === "undefined") {
    return { access: null, refresh: null };
  }

  return {
    access: window.localStorage.getItem(ACCESS_KEY),
    refresh: window.localStorage.getItem(REFRESH_KEY),
  };
}

export function setAdminTokens(access: string, refresh: string): void {
  window.localStorage.setItem(ACCESS_KEY, access);
  window.localStorage.setItem(REFRESH_KEY, refresh);
}

export function clearAdminTokens(): void {
  window.localStorage.removeItem(ACCESS_KEY);
  window.localStorage.removeItem(REFRESH_KEY);
}

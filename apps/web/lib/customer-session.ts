"use client";

const ACCESS_KEY = "chaba_customer_access_token";
const REFRESH_KEY = "chaba_customer_refresh_token";

/**
 * Customer token storage — mirrors lib/admin-session.ts's localStorage
 * pattern exactly, under distinct keys so a customer and an admin session
 * can coexist in the same browser without clobbering each other.
 */
export function getCustomerTokens(): { access: string | null; refresh: string | null } {
  if (typeof window === "undefined") {
    return { access: null, refresh: null };
  }

  return {
    access: window.localStorage.getItem(ACCESS_KEY),
    refresh: window.localStorage.getItem(REFRESH_KEY),
  };
}

export function setCustomerTokens(access: string, refresh: string): void {
  window.localStorage.setItem(ACCESS_KEY, access);
  window.localStorage.setItem(REFRESH_KEY, refresh);
}

export function clearCustomerTokens(): void {
  window.localStorage.removeItem(ACCESS_KEY);
  window.localStorage.removeItem(REFRESH_KEY);
}

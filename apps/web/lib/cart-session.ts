"use client";

const STORAGE_KEY = "chaba_guest_session";

/**
 * Guest identity for cart requests — a client-generated UUID persisted in
 * localStorage, sent as the X-Guest-Session header. Deliberately not a
 * cookie: avoids cross-origin (web:3000 -> api:8000) CORS-credentials
 * complexity entirely, since a plain custom header needs none of that.
 */
export function getSessionToken(): string {
  if (typeof window === "undefined") {
    return "";
  }

  const existing = window.localStorage.getItem(STORAGE_KEY);
  if (existing) {
    return existing;
  }

  const token = crypto.randomUUID();
  window.localStorage.setItem(STORAGE_KEY, token);

  return token;
}

/**
 * Like getSessionToken(), but never creates one — used right after login/
 * register/social-login to decide whether a guest cart merge is even worth
 * attempting. Calling getSessionToken() there instead would mint a brand
 * new, cart-less guest session just to immediately (and pointlessly) try
 * merging it.
 */
export function peekSessionToken(): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  return window.localStorage.getItem(STORAGE_KEY);
}

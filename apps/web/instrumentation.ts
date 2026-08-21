import type { Instrumentation } from "next";

// No-op until a real DSN is set (see .env.local.example) — same
// "inactive until configured" contract as the client-side instrumentation
// and the backend's sentry/sentry-laravel scaffold.
export async function register() {
  const dsn = process.env.SENTRY_DSN;
  if (!dsn) return;

  const Sentry = await import("@sentry/nextjs");
  Sentry.init({
    dsn,
    environment: process.env.SENTRY_ENVIRONMENT ?? process.env.NODE_ENV,
    tracesSampleRate: 0.2,
  });
}

export const onRequestError: Instrumentation.onRequestError = async (error, request, context) => {
  if (!process.env.SENTRY_DSN) return;

  const Sentry = await import("@sentry/nextjs");
  Sentry.captureRequestError(error, request, context);
};

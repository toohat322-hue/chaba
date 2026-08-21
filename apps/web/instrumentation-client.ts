import * as Sentry from "@sentry/nextjs";

// No-op until a real DSN is set (see .env.local.example) — matches the
// backend's sentry/sentry-laravel scaffold, same "inactive until configured"
// contract.
const dsn = process.env.NEXT_PUBLIC_SENTRY_DSN;

if (dsn) {
  Sentry.init({
    dsn,
    environment: process.env.NEXT_PUBLIC_SENTRY_ENVIRONMENT ?? process.env.NODE_ENV,
    tracesSampleRate: 0.2,
  });
}

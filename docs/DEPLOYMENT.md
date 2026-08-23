# Deploying to Render

`render.yaml` at the repo root is a [Render Blueprint](https://render.com/docs/blueprint-spec) — it defines every service CHABA needs (both apps, Postgres, Redis, a queue worker, a scheduler) so a first deploy is one click, then a handful of values you fill in yourself. Nothing that needs a real secret or a business decision is guessed or defaulted in the blueprint.

## 1. Deploy the blueprint

Render dashboard → **New** → **Blueprint** → pick this repo → **Apply**. This creates six things: `chaba-db` (Postgres), `chaba-redis`, `chaba-api`, `chaba-queue-worker`, `chaba-scheduler`, `chaba-web`. The first deploy of `chaba-api`/`chaba-web` will fail health checks — that's expected, because a few required env vars aren't set yet. Fix those next, then redeploy.

## 2. Required — the site won't boot without these

**`APP_KEY`** (on `chaba-api`, `chaba-queue-worker`, `chaba-scheduler` — same value on all three):
```
cd apps/api && php artisan key:generate --show
```
Paste the `base64:...` output into each service's `APP_KEY` env var in the Render dashboard. Generate this once, reuse the same value everywhere — a different key per service would make sessions/encrypted fields readable by only one of them.

**Cloudflare R2** (object storage — product images, hero slides, etc.):
1. Cloudflare dashboard → R2 → **Create bucket** (e.g. `chaba-products`).
2. Bucket → **Settings** → **Public access** → allow it, copy the public URL (`https://pub-xxxxx.r2.dev` or your own custom domain if you attach one) — this is `NEXT_PUBLIC_ASSET_URL` on `chaba-web`.
3. R2 → **Manage API Tokens** → **Create API Token** → permission **Object Read & Write**, scoped to that bucket. Copy the Access Key ID / Secret Access Key.
4. R2 → bucket → note the **Account ID** shown in the endpoint URL — the S3 endpoint is `https://<account_id>.r2.cloudflarestorage.com`.

Fill in on `chaba-api` (+ `chaba-queue-worker` + `chaba-scheduler`, same values on all three):
| Env var | Value |
|---|---|
| `AWS_ACCESS_KEY_ID` | from the R2 API token |
| `AWS_SECRET_ACCESS_KEY` | from the R2 API token |
| `AWS_BUCKET` | your bucket name |
| `AWS_ENDPOINT` | `https://<account_id>.r2.cloudflarestorage.com` |

And on `chaba-web`:
| Env var | Value |
|---|---|
| `NEXT_PUBLIC_ASSET_URL` | the bucket's public URL from step 2 |

**Cross-service URLs** (Render can't auto-fill a path suffix onto another service's URL):
- `chaba-web`'s `NEXT_PUBLIC_API_URL` → `chaba-api`'s Render URL + `/api/v1` (e.g. `https://chaba-api.onrender.com/api/v1`). Look up `chaba-api`'s actual URL in its dashboard page first.
- This is the one env var that needs a **manual redeploy of `chaba-web`** after setting it — build-time `NEXT_PUBLIC_*` vars are baked into the JS bundle, so just saving the value isn't enough on its own.

## 3. Recommended before telling real customers about the site

**WhatsApp number** — `NEXT_PUBLIC_WHATSAPP_NUMBER` on `chaba-web` (digits only, no `+`), *or* set it properly through **Admin → Footer → About** once the site is up (that's the number the floating contact button and WhatsApp checkout actually use — see the comment on `NEXT_PUBLIC_WHATSAPP_NUMBER` in `.env.local.example` for which is which).

**SMS (OTP delivery)** — `SMS_DRIVER=log` ships by default, meaning OTP codes are written to the log instead of texted to anyone. Get a Twilio account, then set on `chaba-api` + `chaba-queue-worker` + `chaba-scheduler`: `SMS_DRIVER=twilio`, `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`.

**Transactional email** — `MAIL_MAILER=log` ships by default (order confirmations, welcome emails, etc. are logged, not sent). Pick a provider (Postmark, AWS SES, Resend, ...) and set `MAIL_MAILER` + its credentials the same way.

**Your first real admin account.** Don't set `ADMIN_SEED_PHONE`/`ADMIN_SEED_PASSWORD` permanently in the dashboard — they'd sit there as a standing credential. Instead: open a Render Shell on `chaba-api` (dashboard → Shell tab) and run
```
ADMIN_SEED_PHONE=<your phone> ADMIN_SEED_PASSWORD=<a real password> php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder --force
```
once, then never set those two env vars in the dashboard at all. AdminUserSeeder matches by phone (`updateOrInsert`), so running it again later with a different phone just adds another Super Admin rather than touching the first one.

**Error monitoring** — `SENTRY_LARAVEL_DSN` (chaba-api) and `NEXT_PUBLIC_SENTRY_DSN`/`SENTRY_DSN` (chaba-web) are no-ops until set. Sign up at sentry.io, create a Laravel project and a Next.js project, paste the two DSNs in.

**A custom domain** instead of `*.onrender.com` — Render dashboard → service → **Settings** → **Custom Domains**, on both `chaba-api` and `chaba-web`. If you do this, update `chaba-web`'s `NEXT_PUBLIC_API_URL`/`NEXT_PUBLIC_SITE_URL` and `chaba-api`'s `FRONTEND_URL`/`APP_URL` to match — right now those are wired to each service's own `*.onrender.com` address via `fromService`, which stops being correct once a custom domain is the real public URL.

## 4. What's already handled for you

- **Migrations + a safe seed set run on every deploy** (`chaba-api`'s `preDeployCommand`) — 58 wilayas, ~1,541 communes, delivery fees, the 8 RBAC roles + permissions, and real footer/about starter copy. Deliberately **not** included: the sample product catalog (`CategorySeeder`/`ProductSeeder`) — those are local-dev demo data, not something that belongs in a real store. See `database/seeders/ProductionSeeder.php`.
- **Backups target R2** (`BACKUP_DISKS=s3`), not the container's local disk — Render's web services have ephemeral storage, so a local-only backup can vanish on the next deploy.
- **HTTPS is correctly detected** behind Render's proxy (`bootstrap/app.php`'s `trustProxies`) — without this, Laravel would think every request arrived over plain HTTP.
- **The queue worker and scheduler run as separate services**, not inside the web process — `chaba-queue-worker` (`php artisan queue:work`) and `chaba-scheduler` (`php artisan schedule:run`, a Render Cron Job firing every minute) both use the exact same Docker image as `chaba-api`, just a different start command.
- **CORS** between the two apps is wired automatically via `FRONTEND_URL`/`ADMIN_URL`-equivalent service references — no manual origin list to maintain.

## 5. Payment gateways, social login

Left disabled (`CIB_ENABLED`, `EDAHABIA_ENABLED`, `GOOGLE_ENABLED`, `FACEBOOK_ENABLED`, `APPLE_ENABLED` all `false`) — per the PRD, cash-on-delivery alone is sufficient for launch; these are a deliberate later decision, not something a deploy should silently turn on. Flipping any of them to `true` also needs real merchant/OAuth credentials filled in alongside it — nothing here should be enabled without those.

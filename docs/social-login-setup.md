# Social Login Setup (Google, Facebook, Apple)

Social login is fully built and tested against mocked providers, but it
stays **off** (`*_ENABLED=false`) until real app credentials are supplied —
no fake/placeholder credentials are ever shipped. This document is the
checklist for turning each provider on for real.

For every provider, the **redirect URI you must register** is:

```
{APP_URL}/api/v1/auth/{provider}/callback
```

e.g. in production, `https://api.chaba.dz/api/v1/auth/google/callback`. In
local dev it's `http://localhost:8000/api/v1/auth/google/callback` (already
the default in `.env.example`).

All values below go in `apps/api/.env` — never commit real secrets to
`.env.example` or anywhere in the repo.

---

## Google

1. Go to [Google Cloud Console](https://console.cloud.google.com/) →
   create (or pick) a project.
2. **APIs & Services → OAuth consent screen** — configure it (app name,
   support email, logo). For production use, this needs Google's
   verification once you request scopes beyond the basic ones (email,
   profile) — the scopes CHABA requests (`openid`, `profile`, `email`) are
   in Google's non-sensitive tier, so verification is usually fast/automatic.
3. **APIs & Services → Credentials → Create Credentials → OAuth client ID**,
   application type **Web application**.
4. Add an **Authorized redirect URI**: `{APP_URL}/api/v1/auth/google/callback`.
5. Copy the generated **Client ID** and **Client Secret**.

```
GOOGLE_ENABLED=true
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxx
GOOGLE_REDIRECT_URI=https://api.chaba.dz/api/v1/auth/google/callback
```

---

## Facebook

1. Go to [Meta for Developers](https://developers.facebook.com/) → **My
   Apps → Create App** → type **Consumer**.
2. Add the **Facebook Login** product to the app.
3. **Facebook Login → Settings** → add a **Valid OAuth Redirect URI**:
   `{APP_URL}/api/v1/auth/facebook/callback`.
4. **App Settings → Basic** — copy the **App ID** and **App Secret**.
5. Before going live, Meta requires **App Review** for the `email`
   permission to work for users outside your team's test-user list — while
   in development mode, only accounts added as Testers/Developers/Admins
   on the app can actually log in.

```
FACEBOOK_ENABLED=true
FACEBOOK_CLIENT_ID=xxxxxxxx
FACEBOOK_CLIENT_SECRET=xxxxxxxx
FACEBOOK_REDIRECT_URI=https://api.chaba.dz/api/v1/auth/facebook/callback
```

---

## Apple (Sign in with Apple)

The most involved of the three — requires a **paid Apple Developer Program
membership** ($99/year) and a private key, not a static secret.

1. [Apple Developer](https://developer.apple.com/account/) → **Certificates,
   Identifiers & Profiles**.
2. **Identifiers → App IDs** — create one if CHABA doesn't have one yet
   (needed even though this is a web flow), enable the **Sign In with
   Apple** capability on it.
3. **Identifiers → Services IDs** — create a new Services ID (e.g.
   `dz.chaba.web`) — **this is your `APPLE_CLIENT_ID`**, not the App ID
   itself. Enable **Sign In with Apple**, click **Configure**, and add:
   - **Domain**: your site's domain (e.g. `chaba.dz`)
   - **Return URL**: `{APP_URL}/api/v1/auth/apple/callback`
4. **Keys** → create a new key, enable **Sign In with Apple**, associate it
   with the App ID from step 2. Download the `.p8` file **immediately** —
   Apple only lets you download it once. Note the **Key ID** shown on this
   page.
5. Your **Team ID** is shown at the top-right of the Apple Developer
   account page (a 10-character string).

```
APPLE_ENABLED=true
APPLE_CLIENT_ID=dz.chaba.web
APPLE_REDIRECT_URI=https://api.chaba.dz/api/v1/auth/apple/callback
APPLE_TEAM_ID=XXXXXXXXXX
APPLE_KEY_ID=XXXXXXXXXX
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
...paste the full contents of the downloaded .p8 file here...
-----END PRIVATE KEY-----"
```

`APPLE_PRIVATE_KEY` must be the **PEM file contents**, not a file path —
the backend never reads from disk for this. Leave `APPLE_CLIENT_SECRET`
unset; it's generated automatically as a short-lived signed JWT from the
four values above (`SocialiteProviders\Apple`'s `AppleToken` class) every
time it's needed — there's nothing to paste for it.

**Apple-specific behavior to know**: Apple only sends the user's name on
the **very first** authorization ever (the backend stores it then; later
logins may omit it, which is already handled). Apple's callback arrives as
an HTTP **POST**, not the GET redirect Google/Facebook use — already
supported (`Route::match(['get', 'post'], ...)`). Apple may also give a
**Private Relay** email address (`@privaterelay.appleid.com`) instead of
the user's real one if they chose "Hide My Email" — this is treated as a
completely normal, valid email throughout the system.

---

## After enabling any provider

- The corresponding "Continue with ..." button appears automatically on
  `/login`, `/register`, and the checkout page — no frontend deploy needed
  beyond the `.env` change (frontend has no provider-specific config at
  all; everything is backend-redirect-driven).
- Manually walk the real consent screen once per provider per environment
  before considering it launched — this is the one part of the feature
  that cannot be automated-tested without real credentials (everything
  else — account creation/linking, staff-account refusal, 2FA, token
  exchange, CSRF/state validation — has automated test coverage using
  Socialite's mocking utilities, see `tests/Feature/Auth/SocialLoginHttpTest.php`,
  `SocialAuthServiceTest.php`, `SocialAccountControllerTest.php`).
- If a provider is ever disabled again (`*_ENABLED=false`), its button
  simply stops appearing — no other code changes needed, matching the same
  convention already used for the CIB/Edahabia payment gateways.

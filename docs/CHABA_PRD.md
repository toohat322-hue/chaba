# CHABA (شابة) — Product Requirements Document

**Version:** 1.0
**Date:** August 13, 2026
**Product:** CHABA E-Commerce Platform
**Market:** Algeria
**Document owner:** Product Management

---

## 1. Executive Summary

CHABA (شابة) is a modern, single-vendor e-commerce platform built specifically for the Algerian market, specializing in premium Saudi and Turkish perfumes and oud (Bakhoor/Oud oil). It combines a premium, mobile-first shopping experience with the operational realities of Algerian commerce: cash-on-delivery (COD) as the dominant payment method, wilaya/commune-based logistics, Arabic-first RTL design, and trust-building mechanics that compensate for Algeria's still-maturing online payment infrastructure.

Unlike informal selling through Instagram DMs or Facebook Marketplace, CHABA gives customers a real storefront: searchable catalog, transparent pricing and stock, order tracking, reviews, secure checkout, and predictable delivery — while giving the business real infrastructure: inventory control, analytics, promotions, and a full admin back office.

This document specifies product vision, target users, functional and non-functional requirements, UX/UI system, information architecture, database and API design, security, performance, admin tooling, business rules, edge cases, user stories, MVP scope, roadmap, QA strategy, and launch checklist. It is written to be directly actionable by designers, engineers, and QA.

**Key assumptions (explicitly flagged since not specified by stakeholder):**

| # | Assumption | Rationale |
|---|---|---|
| A1 | CHABA is single-vendor (one seller/brand), not a marketplace, at launch. | Prompt specifies "single-vendor e-commerce platform." Marketplace is a stated future direction. |
| A2 | Web-first (responsive web app); native mobile apps are post-MVP. | Prompt lists "evolve into mobile apps" as future scalability, implying web-first now. |
| A3 | COD is the primary payment method at launch; online payment (CIB/Edahabia) is architected in parallel and may launch slightly after COD if gateway onboarding takes longer. | Reflects real Algerian market conditions — COD dominates e-commerce today. |
| A4 | Delivery is fulfilled via third-party courier/delivery partners (Yalidine-style integration), not an owned fleet. | Standard for Algerian e-commerce SMEs; owned fleet is a future scalability item. |
| A5 | 58 wilayas is the current official division and is used as the canonical location list. | Matches prompt instruction explicitly. |
| A6 | Currency is DZD with no decimal display in UI (Algerian convention), stored as an integer (centimes) internally. | Avoids floating point rounding errors. |
| A7 | WhatsApp notifications are treated as a V1/V2 feature pending WhatsApp Business API approval, not guaranteed for MVP. | Prompt says "if legally/technically appropriate." |
| A8 | Category focus is premium Saudi and Turkish perfumes and oud (Bakhoor/Oud oil), not general fashion/beauty/lifestyle goods. Top-level catalog navigation: Saudi Perfumes, Turkish Perfumes, Oud Oil, Exclusive Offers. | Product direction confirmed by stakeholder (2026-08-13), inspired by a reference storefront design. Supersedes the original general "fashion, beauty, accessories, lifestyle goods" framing in earlier revisions of this document. Personas' specific buying interests (Section 4) and catalog examples (Section 7.3) should be read through this lens; underlying architecture (Sections 12–14) is category-agnostic and required no structural changes. |

---

## 2. Product Vision

**Vision:** To become the most trusted, premium online shopping destination in Algeria — a place where Algerians shop with the same confidence, ease, and delight they'd expect from any world-class e-commerce brand, in their own language and on their own terms.

**Mission:** Replace fragmented, low-trust social-media selling with a fast, transparent, Arabic-first shopping experience that respects how Algerians actually pay, communicate, and receive deliveries.

**Core value proposition:**

| For | CHABA offers |
|---|---|
| Customers | A trustworthy, fast, RTL-native store with real stock, real prices, real tracking, and cash-on-delivery — without the DM back-and-forth. |
| The business | A scalable commerce engine: inventory, promotions, analytics, and an admin dashboard that a small team can run and grow. |

**Target customers:** Young, mobile-first Algerian consumers (primarily 18—35) shopping for premium Saudi and Turkish perfumes and oud (Bakhoor/Oud oil), who are used to Instagram/Facebook perfume vendors but frustrated by unverified authenticity and unreliable service.

**Customer problems CHABA solves:** No reliable way to see real stock, price, or order status when buying via social media; slow manual checkout via DM/comments ("price?", "disponible?", "combien la livraison?"); no structured way to track an order or resolve a complaint; trust concerns about seller legitimacy and COD reliability; poor or nonexistent Arabic RTL experience on most Algerian web stores, which are usually LTR templates awkwardly translated.

**Business problems CHABA solves:** No system of record for orders, inventory, or customers; no way to prevent overselling out-of-stock items; no analytics on what sells, where, or to whom; no promotions engine, so discounting is manual and inconsistent; no scalable delivery-fee logic across 58 wilayas.

**Competitive advantages over Instagram/Facebook/manual stores:**

| Dimension | Instagram/Facebook selling | Generic e-commerce template | CHABA |
|---|---|---|---|
| Checkout | Manual DM negotiation | Often LTR-only, poor Arabic | Native RTL, one-flow checkout |
| Stock accuracy | Manual, frequently wrong | Sometimes tracked | Real-time, reservation-based |
| Delivery fees | Ad hoc, inconsistent | Flat or missing wilaya logic | Wilaya-based fee table, transparent at checkout |
| COD | Informal, disputed | Rarely modeled properly | Full COD lifecycle with confirmation & reconciliation |
| Trust signals | None structured | Generic | Verified reviews, order tracking, delivery status |
| Language | Arabic dialect only in captions | Poor/no Arabic UI | Arabic, French, English as first-class UI languages |

**Product positioning:** CHABA is positioned as a premium, modern Algerian perfume house — closer to a boutique DTC fragrance brand experience than a mass marketplace — trustworthy enough to replace Instagram perfume shopping (where authenticity is a constant concern), fast enough to feel international-grade, and local enough to fit Algerian payment and delivery habits perfectly.

**Why customers choose CHABA over Instagram/Facebook/traditional stores:** they get instant, accurate stock and pricing instead of waiting for a DM reply; a real checkout instead of negotiating in comments; a tracked order number instead of "the driver will call you"; a native Arabic RTL interface that feels designed for them, not translated at them; and visible proof (reviews, ratings, order history) that the seller is real and reliable.

**Short-term goals (0—6 months):** launch MVP with full catalog, cart, checkout, COD, order tracking, and admin dashboard; achieve reliable delivery-fee accuracy across all 58 wilayas; establish trust through reviews, transparent policies, and order tracking.

**Long-term goals (12+ months):** introduce online payments (CIB/Edahabia) at scale to reduce COD share and its associated return/refusal costs; expand into a multi-vendor marketplace; launch native mobile apps; support multiple warehouses and eventually cross-border (Maghreb) expansion; build a recommendation engine driven by first-party behavioral data.

---

## 4. Target Users

### 4.1 Customer Personas

**1. Young Algerian Online Shopper — "Amina, 22, student, Oran"**
Demographics: 18—25, student or early career, Algiers/Oran/Constantine, active on Instagram/TikTok. Goals: discover trending Saudi/Turkish perfumes and oud scents fast, get the best fragrance for the least price. Pain points: hard to verify sellers and product authenticity, slow DM replies, unclear delivery costs. Behaviors: browses on mobile during evenings, screenshots products to compare, buys after seeing reviews or influencer mentions. Needs: fast mobile browsing, clear pricing including delivery, easy COD. Frustrations: sellers who don't reply or run out of stock after she's already "ordered." Buying motivations: trend-driven, social proof, discounts.

**2. Mobile-First Shopper — "Yacine, 27, tradesman, Sétif"**
Demographics: uses only a mid-range Android phone, limited/variable data connection, rarely uses a laptop. Goals: buy practical goods quickly without friction. Pain points: heavy websites that load slowly on 3G/4G, small tap targets, forms that don't fit Algerian phone/address formats. Behaviors: shops in short bursts, abandons slow pages within seconds. Needs: fast-loading, lightweight pages, large touch targets, autofill-friendly forms. Frustrations: sites that assume a desktop layout. Buying motivations: convenience, speed, reliability.

**3. Price-Sensitive Shopper — "Nour, 30, mother of two, Blida"**
Demographics: household budget manager, compares prices across platforms. Goals: find the best value, use discounts/coupons effectively. Pain points: hidden delivery fees, unclear total cost until the end. Behaviors: adds to cart then abandons if the delivery fee surprises her; waits for sales. Needs: transparent total cost early, visible discount badges, coupon field in cart. Frustrations: price changes between browsing and checkout. Buying motivations: promotions, free shipping thresholds, perceived value.

**4. Returning Customer — "Sarah, 26, marketing employee, Algiers"**
Demographics: has ordered before, trusts the brand. Goals: reorder quickly, track a current order, use saved addresses. Pain points: having to re-enter address/phone every time. Behaviors: checks order status proactively, leaves reviews when satisfied. Needs: saved addresses, order history, quick reorder, loyalty-style recognition (e.g., coupons). Frustrations: no visibility into where her package is. Buying motivations: trust built from prior good experience, personalized recommendations.

**5. Gift Shopper — "Karim, 33, engineer, Annaba"**
Demographics: buying for a spouse/family member, less familiar with the catalog. Goals: find a suitable gift quickly, ensure it arrives on time for an occasion. Pain points: uncertain about sizing/fit for someone else, worried about delivery timing. Behaviors: relies heavily on product photos, reviews, and delivery estimates. Needs: clear delivery estimate, gifting-friendly presentation, easy returns if wrong. Frustrations: vague delivery windows. Buying motivations: occasion urgency, presentation quality, return flexibility.

### 4.2 Internal / Operational Personas

**6. Store Administrator — "Lina, 29, store owner/operator"**
Demographics: manages the full catalog, pricing, and promotions, possibly non-technical. Goals: keep the catalog accurate, run promotions, monitor sales. Pain points: needs a dashboard that doesn't require engineering help for routine tasks. Behaviors: logs in daily, checks revenue and low-stock alerts. Needs: intuitive product/inventory management, clear sales dashboards, wilaya-level sales visibility. Frustrations: complex tools built for enterprise retailers, not a small Algerian team. Motivations: growing revenue, minimizing operational errors.

**7. Customer Support Employee — "Mounir, 24, support agent"**
Demographics: handles order inquiries, complaints, and COD confirmation calls. Goals: resolve customer issues fast, confirm orders before shipping. Pain points: needs full order/customer context in one screen; needs to update order status and communicate it. Behaviors: works from the admin order list, filters by status, calls customers for COD confirmation. Needs: order search, status update tools, notes field, customer contact history. Frustrations: fragmented data across tools. Motivations: fast resolution, low cancellation/return rates.

**8. Warehouse/Inventory Employee — "Ryma, 26, warehouse staff"**
Demographics: manages stock counts, packs orders. Goals: know exactly what's in stock, avoid picking errors. Pain points: stock counts that don't match reality, no alerts on low stock. Behaviors: uses the admin inventory screen to adjust stock, mark items packed/ready to ship. Needs: SKU-level accuracy, low-stock alerts, stock adjustment history/audit trail. Frustrations: overselling causing cancellations after packing has begun. Motivations: operational accuracy, fewer post-sale corrections.

---

## 5. User Problems (Summary)

| Problem | Affected persona(s) | CHABA solution |
|---|---|---|
| No verified stock/pricing before purchase | Amina, Nour | Real-time stock + price display, reservation-based cart |
| Slow, manual social-media checkout | All shoppers | One-flow guided checkout, guest checkout |
| Hidden or unpredictable delivery costs | Nour, Karim | Wilaya-based fee shown before payment step |
| No order tracking / status visibility | Sarah, Karim | Order tracking page with delivery status timeline |
| Poor Arabic RTL experience elsewhere | All Arabic-speaking users | RTL-first design system, native Arabic search |
| Slow pages on mobile networks | Yacine | Mobile-first performance targets, lazy loading, CDN |
| No system of record for operations | Lina, Mounir, Ryma | Full admin dashboard: orders, inventory, customers |
| Overselling out-of-stock items | Ryma | Stock reservation + oversell prevention logic |

---

## 6. Core Customer Journey

**Flow:** Discovery — Homepage — Categories — Search — Product Details — Add to Cart — Checkout — Address — Delivery Method — Payment — Order Confirmation — Order Tracking — Delivery — Review — Repeat Purchase.

| Stage | User goal | Friction risk | UX solution |
|---|---|---|---|
| Discovery (social/ads/search) | Land on something relevant | Landing page mismatch, slow load | Fast landing pages matched to ad/campaign source, <2.5s LCP |
| Homepage | Get oriented, find something interesting | Overload, unclear navigation | Clear hero, curated categories, best sellers, RTL nav |
| Categories | Narrow down options | Too many/too few filters | Smart filters (size, color, price, category), sort, badges |
| Search | Find a specific item fast | Arabic typos, no Arabic-dialect matches | Typo-tolerant, multi-language, autocomplete, recent/popular searches |
| Product Details | Decide to buy | Unclear stock/size/return policy | Clear stock status, size guide, delivery estimate, reviews, return policy visible |
| Add to Cart | Confirm selection | Price/stock mismatch at cart | Real-time stock validation on add-to-cart |
| Checkout | Provide info fast | Long forms, forced signup | Guest checkout, minimal required fields, autofill-friendly |
| Address | Enter valid delivery address | Complex/unfamiliar address formats | Wilaya — commune cascading dropdowns, phone format validation |
| Delivery Method | Choose delivery option | Unclear fee/timing | Fee and ETA shown per wilaya before confirming |
| Payment | Choose how to pay | Distrust of online payment | COD default and prominent; online payment optional, clearly secured |
| Order Confirmation | Feel confident the order is real | No confirmation, anxiety | Order number, SMS/email confirmation, summary page |
| Order Tracking | Know where the order is | No visibility, "the driver will call" | Status timeline: Confirmed — Processing — Shipped — Out for Delivery — Delivered |
| Delivery | Receive product, pay COD if applicable | Missed delivery, wrong address | Courier contact info, delivery attempt notifications, reschedule flow |
| Review | Share feedback | Friction to leave a review | One-tap star rating post-delivery prompt, optional detailed review |
| Repeat Purchase | Buy again easily | Re-entering everything | Saved addresses, order history, personalized recommendations, reorder button |

---

## 7. Functional Requirements

Priority key: **P0** = MVP-blocking, **P1** = first update (V1), **P2** = advanced (V2), **P3** = nice-to-have / evaluate later.

### 7.1 Authentication

| Feature | Description | Priority |
|---|---|---|
| Register | Phone or email + password; Algerian phone format enforced | P0 |
| Login | Phone/email + password | P0 |
| OTP (SMS) | 4—6 digit OTP for phone verification and login option | P0 |
| Password reset | Via SMS OTP or email link | P0 |
| Guest checkout | No account required to purchase; phone required for order/COD confirmation | P0 |
| Google authentication | OAuth sign-in | P1 |
| Email verification | Verify email if provided | P1 |
| Two-factor for admin | Required for Admin/Super Admin roles | P1 |

Business rules: phone numbers are the primary identity for Algerian users (email optional). OTP codes expire in 5 minutes, max 5 attempts, then 15-minute lockout. Passwords hashed with bcrypt/argon2, minimum 8 characters.

### 7.2 Homepage

Hero banner (rotating, campaign-driven), category shortcuts, featured products, best sellers (computed from sales velocity), new arrivals (last 14 days), active discounts/sale rail, personalized recommendations (recently viewed + purchase history based, post-MVP ML), promotional banners (admin-configurable), trust indicators (COD available, verified reviews count, delivery coverage, return policy), recently viewed products (session + account based).

### 7.3 Product Catalog

Categories and subcategories (up to 3 levels), filters (category, price range, color, size, brand, rating, in-stock only, discount), sorting (relevance, price asc/desc, newest, best selling, rating), pagination with infinite scroll on mobile / paginated on desktop, product badges (New, Best Seller, Sale %, Low Stock, Out of Stock), live stock status, discount percentage badge computed from compare-at price, product variants (color/size combinations each with own SKU, price override, and stock).

### 7.4 Search

Autocomplete (product name, category, brand) starting at 2 characters; search suggestions ranked by popularity and relevance; recent searches (per user/session, last 10); popular searches (admin/analytics driven); typo tolerance (edit-distance fuzzy matching); Arabic search (handles Arabic script, common dialect spellings, diacritics-insensitive); French and English search (accent-insensitive, e.g., "robe" matches "Robe été"); no-result state offers spelling suggestions, popular products, and category browse fallback.

### 7.5 Product Details

Image gallery with pinch/click zoom, product name, SKU, rich description, specifications table, variant selectors (color/size) with live price/stock update, price and discount display, stock status (in stock / low stock — "only 3 left" / out of stock), delivery estimate by wilaya (calculated from default or entered address), return policy summary with link to full policy, reviews and average rating, related products, frequently bought together, Add to Cart, Buy Now (skip to checkout), Wishlist toggle.

### 7.6 Cart

Line items with thumbnail, name, variant, price, quantity stepper, remove; save-for-later; coupon code field with live validation; delivery fee estimate (wilaya selector or default); subtotal, discount, delivery, total breakdown; real-time stock validation (flags if quantity exceeds available stock); cart persistence (localStorage/session for guests, synced to account on login, 30-day retention).

### 7.7 Checkout

Six-step frictionless flow: (1) Customer information — name, phone, optional email, guest or logged-in; (2) Delivery address — wilaya — commune cascading select, street address, optional postal code, landmark note; (3) Delivery method — home delivery or pickup point (stop-desk) if supported, with fee and ETA per option; (4) Payment method — Cash on Delivery (default) or Online Payment (CIB/Edahabia); (5) Order review — full summary, editable before submit; (6) Confirmation — order number, summary, tracking link, SMS/email sent. Supports guest checkout, coupon codes, order notes (free text for delivery instructions), and real-time delivery fee calculation.

### 7.8 Algerian Market Requirements (Critical)

**Location model:** `wilaya (58) — commune — address line — optional postal code`. Wilaya and commune are selected via cascading dropdowns (never free text) to guarantee valid delivery-fee lookup. Address fields: full name, phone (primary + optional secondary), wilaya, commune, address line (street/building/floor), landmark/notes (optional, common in Algeria where formal addressing is inconsistent), postal code (optional, auto-suggested from commune when available).

**Phone validation:** Algerian mobile format `0[5-7]XXXXXXXX` (10 digits, prefixes 05/06/07) stored internationally as `+213[5-7]XXXXXXXX`. Landline `0[2-4]XXXXXXXX` accepted for secondary contact only. Validation occurs client-side (regex) and server-side.

**Delivery:**

| Feature | Description |
|---|---|
| Home delivery | Door-to-door via courier partner, available in all 58 wilayas at varying fees/ETAs |
| Stop-desk / pickup delivery | Customer picks up from courier's local office; lower fee, offered where courier partner supports it |
| Delivery zones | Wilaya is the primary zone unit; commune-level overrides supported for remote areas |
| Wilaya-based delivery fees | Fee table per wilaya, per delivery type (home vs. stop-desk), configurable in admin |
| Estimated delivery time | Per-wilaya ETA (e.g., Algiers 24—48h, remote south wilayas 4—7 days) |
| Delivery status | Pending — Picked Up — In Transit — Out for Delivery — Delivered / Failed / Returned |
| Courier information | Courier/partner name, tracking reference, driver contact when available |
| Failed delivery | Logged with reason (customer unavailable, wrong address, refused); triggers retry workflow |
| Customer unavailable | Auto-retry up to 2 attempts, customer notified each attempt, then escalated to support |
| Returned shipment | Courier returns to origin; inventory restocked; order marked Returned |

**Cash on Delivery (COD) — full workflow:**

1. **Order creation:** customer selects COD at checkout; no payment captured; order status = `Pending`.
2. **Confirmation:** support team or automated SMS/call-back OTP confirms the order (configurable — small order volumes may use manual call confirmation; higher volumes use SMS confirmation link) within a defined SLA (e.g., 24h). Order status = `Confirmed`.
3. **Processing/Shipment:** order is packed, handed to courier; status = `Processing` — `Shipped`.
4. **Delivery:** courier attempts delivery; on success, cash is collected by courier; status = `Delivered`, `payment_status = collected`.
5. **Failed delivery:** courier logs failure reason; retry per policy; after max attempts, order = `Returned`.
6. **Return:** returned stock is reconciled back into inventory; order marked `Returned`, `payment_status = not_collected`.
7. **Reconciliation:** courier partner remits collected COD cash on a settlement cycle; admin Finance role reconciles remitted amounts against delivered-COD orders in the Payments module, flagging discrepancies.

**COD-specific business rules:** orders above a configurable COD threshold (e.g., 15,000 DZD) may require mandatory phone confirmation before shipping to reduce refusal risk; repeat COD-refusal customers may be flagged and restricted to prepaid-only in V1.

### 7.9 Payment System Architecture

CHABA uses a **provider-agnostic payment abstraction layer** so new gateways can be added without touching checkout logic.

```
Checkout — PaymentService.createPayment(order, method)
              — PaymentProviderInterface (COD | CIB | Edahabia | future)
              — provider.initiate() — provider.handleWebhook() — PaymentService.updateStatus()
```

Each provider implements a common interface: `initiate(order)`, `verify(transactionRef)`, `refund(transactionRef, amount)`, `handleWebhook(payload, signature)`. COD is modeled as a "provider" too (a no-op initiate, with `payment_status` transitioning on delivery events instead of a gateway callback), which keeps the order/checkout flow identical regardless of method.

| Concept | States |
|---|---|
| Payment status | `pending`, `authorized`, `captured`, `failed`, `refunded`, `partially_refunded`, `cod_pending_collection`, `cod_collected` |
| Transaction status | `initiated`, `processing`, `success`, `failed`, `cancelled`, `expired` |
| Refund | Full refund via provider API (online) or manual ledger entry (COD, since cash was never captured digitally) |
| Partial refund | Supported for both online and COD-collected orders (e.g., partial item return) |
| Webhook | Signature-verified, idempotent (dedup by provider transaction ID), retried on 5xx |
| Reconciliation | Daily job matches provider settlement reports and courier COD remittances against internal order/payment records; discrepancies flagged in admin Finance view |

Future providers (e.g., additional local gateways, mobile wallets) are added by implementing `PaymentProviderInterface` and registering in a provider config table — no checkout or order-model changes required.

### 7.10 Order Management (Lifecycle)

`Pending — Confirmed — Processing — Ready to Ship — Shipped — Out for Delivery — Delivered`

Alternative/terminal states: `Cancelled`, `Failed`, `Returned`, `Refunded`, `Partially Refunded`.

Each order stores: order number (human-readable, e.g., `CHB-2026-000123`), order items (product/variant/qty/price snapshot), customer reference (or guest snapshot), delivery address snapshot, payment record, delivery/shipment record, totals (subtotal, discount, delivery fee, tax if applicable, grand total), notes (customer + internal), and a full status history log (status, timestamp, actor, reason).

### 7.11 Customer Account

Profile (name, phone, email), saved addresses (multiple, with a default), order history with tracking, wishlist, recently viewed, reviews written, notification preferences/inbox, saved/available coupons, account settings (language preference, password, notification channels).

### 7.12 Reviews & Ratings

Star rating (1—5) required, written review optional, up to 5 photos optional, "Verified Purchase" badge (only shown if reviewer has a delivered order for that product), moderation queue (new reviews held for admin approval before publishing, configurable auto-publish for verified purchases in V1), report-review flow for other customers, average rating recalculated on every new/edited/removed review.

### 7.13 Wishlist

Add/remove from PDP and catalog cards; guest wishlist stored in localStorage and merged into account on login; out-of-stock items remain in wishlist with an "out of stock" indicator; price-drop notification opt-in per item (P1).

### 7.14 Promotions

Coupon codes (percentage, fixed amount, free shipping), Buy X Get Y, product-specific and category-specific discounts, minimum order value threshold, start/end date scheduling, usage limits (global and per-customer), customer-specific/targeted coupons (e.g., first-order, win-back). Promotions engine evaluates cart contents against active rules and applies the best eligible discount (configurable: stackable vs. best-single-discount).

### 7.15 Notifications

Channels: in-app, email, SMS (primary for Algerian users), WhatsApp (V1/V2, pending Business API approval), push (post-MVP native app). Events: account creation, OTP, order confirmation, order status change, shipment update, delivery, cancellation, refund, promotion announcement, price drop (wishlist), back-in-stock alert.

---

## 8. Non-Functional Requirements

| Category | Requirement |
|---|---|
| Performance | Homepage/PDP LCP < 2.5s on 4G mid-range Android; API p95 < 300ms for reads, < 800ms for checkout writes |
| Availability | 99.5% uptime target for MVP; 99.9% for V1 |
| Scalability | Stateless app servers behind a load balancer; horizontal scaling; read replicas for DB as traffic grows |
| Localization | Full Arabic RTL, French, English parity — no feature ships in only one language |
| Accessibility | WCAG 2.1 AA color contrast, keyboard navigability, alt text on all product images |
| Security | OWASP Top 10 mitigations, encrypted data at rest and in transit, RBAC for admin |
| Data retention | Order and payment records retained minimum 5 years for tax/legal purposes |
| Browser/device support | Latest 2 versions of Chrome/Safari/Firefox/Edge; Android 9+ and iOS 14+ mobile browsers |
| Observability | Centralized logging, error tracking (e.g., Sentry), uptime monitoring, alerting on payment/webhook failures |

---

## 9. UX/UI Requirements & Design System

**Principles:** modern, premium, minimal, fast, mobile-first, RTL-first for Arabic, accessible, responsive.

**Color palette (from brand identity):**

| Token | Color | Usage |
|---|---|---|
| `--color-primary` | Deep dark teal / forest green (#0E3B36 approx.) | Primary actions, header, brand elements |
| `--color-accent` | Muted gold (#C9A24B approx.) | Highlights, badges, price emphasis, CTAs on dark surfaces |
| `--color-background` | Warm off-white (#FAF7F0 approx.) | Page background |
| `--color-ink` | Dark charcoal (#22272B approx.) | Body text, headings |
| `--color-success` | Muted green | Success states, in-stock |
| `--color-error` | Muted red | Errors, out-of-stock |
| `--color-warning` | Amber/gold-tinted | Low stock, pending states |

**Typography:** Arabic — a modern, high-legibility Arabic typeface (e.g., IBM Plex Sans Arabic or Cairo) as primary; French/English — a complementary geometric sans (e.g., Inter or Manrope). Type scale: Display 32—40px, H1 28px, H2 22px, H3 18px, Body 16px, Small 14px, Caption 12px, all with 1.4—1.6 line height for Arabic legibility.

**Spacing & shape:** 8px base spacing scale (4/8/12/16/24/32/48/64). Border radius: 8px (inputs/buttons), 16px (cards), 24px (modals/sheets). Shadows: soft, low-opacity elevation (e.g., `0 4px 16px rgba(0,0,0,0.08)`), no harsh drop shadows to preserve the premium/minimal feel.

**Components:** Buttons (primary filled teal, secondary outline, ghost/text, gold CTA for promotions), Inputs (floating label, RTL-mirrored icons, inline validation), Cards (product card with image, badge, name, price, rating, quick-add), Navigation (sticky header with RTL-mirrored icon order, bottom tab bar on mobile), Modals/Sheets (bottom sheet on mobile, centered modal on desktop), Toasts (non-blocking, auto-dismiss, RTL-aware slide direction), Alerts/Banners, Badges (New/Sale/Low Stock/Best Seller), Tables (admin data tables, sticky header, sortable columns), Empty states (illustration + message + primary action, e.g., empty cart — "Browse categories"), Loading states (skeleton screens, not spinners, for catalog/PDP), Error states (clear message + retry action, never a raw error code to the customer).

**Responsive behavior:** Mobile (<768px) — single column, bottom nav, sticky add-to-cart bar; Tablet (768—1024px) — 2-column catalog grid, side filters as a drawer; Desktop (>1024px) — persistent left/right filter sidebar, 3—4 column catalog grid, mega-menu category navigation. RTL mirroring applies to layout direction, icons (back/forward, carousels), and text alignment across all breakpoints — Arabic is the default/primary layout direction, not a mirrored afterthought of an LTR design.

---

## 10. Information Architecture & 11. Sitemap

**Customer-facing:** Homepage; Categories; Category Details; Search; Search Results; Product Details; Cart; Checkout; Order Confirmation; Order Tracking; Login; Register; OTP Verification; Forgot Password; Account (Profile, Orders, Order Details, Wishlist, Addresses, Reviews, Notifications, Settings); About; Contact; FAQ; Terms; Privacy; Shipping Policy; Return Policy.

**Admin:** Dashboard; Products; Categories; Orders; Customers; Inventory; Coupons; Promotions; Reviews; Delivery; Payments; Reports; Notifications; Settings; Roles & Permissions.

**Detailed screen specifications (representative key screens; remaining screens follow the same structure):**

**Homepage**
- Purpose: orient the shopper, surface high-intent products, build trust immediately.
- Components: header/nav, hero banner carousel, category shortcuts, best sellers rail, new arrivals rail, promotional banner, trust indicators strip, recently viewed rail, footer.
- User actions: browse category, tap product card, search, switch language.
- Business rules: best sellers computed from trailing 14-day sales velocity; banners scheduled by admin with start/end dates.
- Empty state: n/a (always has content; falls back to generic categories if no featured products configured).
- Loading state: skeleton hero + skeleton product cards.
- Error state: cached last-known content shown with a subtle "having trouble loading latest content" banner.
- Success state: fully populated, fast-loading page.
- Mobile: single column rails, swipeable hero, bottom tab nav. Desktop: multi-column grid rails, mega-menu nav.

**Product Details Page (PDP)**
- Purpose: give the customer everything needed to decide and buy.
- Components: image gallery/zoom, title, SKU, price/discount, variant selectors, stock status, delivery estimate widget, add-to-cart/buy-now/wishlist, description tabs, specifications, reviews, related products, frequently bought together.
- User actions: select variant, zoom image, add to cart, buy now, add to wishlist, read reviews, enter wilaya to see delivery estimate.
- Business rules: price/stock update instantly on variant change; buy-now skips to checkout with the item pre-loaded; out-of-stock variants are disabled, not hidden.
- Empty/loading/error states: skeleton gallery + text blocks while loading; "product unavailable" state if deleted/deactivated with a link back to category.
- Mobile: sticky bottom add-to-cart bar. Desktop: sticky right-column buy box while scrolling description.

**Cart**
- Purpose: let the customer review and adjust before checkout.
- Components: line items, quantity stepper, remove/save-for-later, coupon field, order summary (subtotal/discount/delivery/total), checkout CTA.
- Business rules: stock re-validated on cart open and before checkout; price reflects current catalog price with a "price changed" notice if different from when added.
- Empty state: "Your cart is empty" + browse CTA. Error state: item removed automatically if discontinued, with a clear notice.
- Mobile: full-screen cart view. Desktop: cart page or slide-over drawer.

**Checkout**
- Purpose: convert cart into a confirmed order with minimum friction.
- Components: step indicator, customer info form, address form (wilaya/commune cascading select), delivery method selector with fee/ETA, payment method selector, order review, submit.
- Business rules: guest checkout allowed; all fields validated inline; delivery fee recalculated on wilaya change; order is only created after successful validation of stock and address.
- Error state: field-level inline errors in the user's selected language; network-failure state preserves entered data and offers retry.
- Mobile: single-column step-by-step flow. Desktop: two-column (form left, summary right, sticky).

**Order Tracking**
- Purpose: give visibility into order and delivery status.
- Components: order number lookup (for guests) or account order list, status timeline, courier info, delivery address, items summary.
- Business rules: status updates pulled from courier integration and internal status changes; guest tracking requires order number + phone match.
- Empty state: "Order not found" with support contact. Mobile: vertical timeline. Desktop: timeline + summary side-by-side.

**Admin Dashboard (Home)**
- Purpose: give store operators an at-a-glance operational and sales view.
- Components: revenue chart, orders today/this week, customer growth, low-stock alerts, best-selling products, sales-by-wilaya map/table, conversion rate, average order value.
- Business rules: role-based visibility (Finance sees revenue detail; Support does not, by default).
- Empty state: "No data yet" for a brand-new store. Mobile: stacked cards, key metrics only. Desktop: full multi-widget grid.

*(Remaining customer screens — Categories, Category Details, Search/Search Results, Login/Register/OTP/Forgot Password, Account sub-pages, Wishlist, Addresses, Reviews, Notifications, About/Contact/FAQ/Terms/Privacy/Shipping/Return Policy — and remaining admin screens — Products, Categories, Orders, Customers, Inventory, Coupons, Promotions, Reviews, Delivery, Payments, Reports, Notifications, Settings, Roles — follow the same purpose/components/actions/rules/states/responsive specification pattern and are detailed in the functional requirements and admin dashboard sections above/below.)*

---

## 12. Database Architecture

Relational schema (PostgreSQL). Primary keys are UUIDs unless noted. Money stored as integer DZD centimes.

**users** — id (PK), full_name, phone (unique, required), email (unique, optional), password_hash, role_id (FK roles, null for customers), preferred_language (enum: ar/fr/en), is_guest (bool), phone_verified_at, email_verified_at, status (active/blocked), created_at, updated_at. Index: phone, email.

**roles** — id (PK), name (Super Admin, Admin, Order Manager, Product Manager, Inventory Manager, Customer Support, Marketing Manager, Finance Manager), description.

**permissions** — id (PK), key (e.g., `orders.edit`), description.

**role_permissions** — role_id (FK), permission_id (FK). Composite PK.

**addresses** — id (PK), user_id (FK users, nullable for guest-order snapshots), full_name, phone, wilaya_code (FK wilayas), commune_id (FK communes), address_line, landmark, postal_code (optional), is_default (bool), created_at.

**wilayas** — code (PK, 01—58), name_ar, name_fr, name_en.

**communes** — id (PK), wilaya_code (FK), name_ar, name_fr, name_en.

**categories** — id (PK), parent_id (FK categories, nullable, up to 3 levels), name_ar, name_fr, name_en, slug (unique, indexed), image_url, sort_order, is_active.

**products** — id (PK), category_id (FK), name_ar, name_fr, name_en, slug (unique, indexed), description_ar/fr/en, base_price, compare_at_price (nullable), status (draft/active/archived), seo_title, seo_description, avg_rating, review_count, created_at, updated_at. Index: slug, category_id, status.

**product_variants** — id (PK), product_id (FK), sku (unique, indexed), color, size, price_override (nullable), weight, created_at.

**product_images** — id (PK), product_id (FK), variant_id (FK, nullable), url, sort_order, alt_text.

**inventory** — id (PK), variant_id (FK, unique), stock_quantity, reserved_quantity, available_quantity (computed = stock — reserved), low_stock_threshold, updated_at. Index: variant_id.

**inventory_adjustments** — id (PK), variant_id (FK), delta, reason (restock/correction/return/damage), actor_id (FK users), created_at. (Audit trail for all stock changes.)

**carts** — id (PK), user_id (FK, nullable for guest), session_token (for guest carts), status (active/converted/abandoned), created_at, updated_at.

**cart_items** — id (PK), cart_id (FK), variant_id (FK), quantity, price_snapshot, created_at.

**wishlists** — id (PK), user_id (FK, nullable), session_token (guest), variant_id (FK), created_at. Unique: (user_id, variant_id).

**orders** — id (PK), order_number (unique, indexed), user_id (FK, nullable for guest), guest_name, guest_phone, guest_email, address_id (FK, snapshot), delivery_method (home/pickup), payment_method (cod/cib/edahabia), payment_status, order_status, subtotal, discount_total, delivery_fee, tax_total, grand_total, coupon_id (FK, nullable), notes, created_at, updated_at. Index: order_number, user_id, order_status, created_at.

**order_items** — id (PK), order_id (FK), variant_id (FK), product_name_snapshot, sku_snapshot, unit_price, quantity, line_total.

**order_status_history** — id (PK), order_id (FK), status, note, actor_id (FK users, nullable for system), created_at.

**payments** — id (PK), order_id (FK), provider (cod/cib/edahabia), transaction_ref (nullable), status, amount, currency (DZD), raw_response (jsonb), created_at, updated_at. Index: order_id, transaction_ref.

**refunds** — id (PK), payment_id (FK), amount, reason, status, actor_id (FK users), created_at.

**shipments** — id (PK), order_id (FK), courier_partner, tracking_number, status (pending/picked_up/in_transit/out_for_delivery/delivered/failed/returned), estimated_delivery_date, delivered_at, failure_reason, created_at, updated_at. Index: order_id, tracking_number.

**coupons** — id (PK), code (unique, indexed), type (percentage/fixed/free_shipping/bxgy), value, min_order_value, start_date, end_date, usage_limit_total, usage_limit_per_customer, is_active.

**coupon_usages** — id (PK), coupon_id (FK), user_id (FK, nullable), order_id (FK), used_at.

**promotions** — id (PK), name, scope (product/category/cart), target_id (nullable), discount_type, discount_value, start_date, end_date, is_active.

**reviews** — id (PK), product_id (FK), user_id (FK), order_item_id (FK, nullable — links to verify purchase), rating (1—5), title, body, is_verified_purchase (bool), status (pending/approved/rejected), created_at.

**review_images** — id (PK), review_id (FK), url.

**review_reports** — id (PK), review_id (FK), reporter_user_id (FK), reason, created_at.

**notifications** — id (PK), user_id (FK, nullable for guest via phone), channel (in_app/email/sms/whatsapp/push), event_type, payload (jsonb), status (queued/sent/failed), sent_at, created_at.

**product_views** — id (PK), user_id (FK, nullable), session_token, product_id (FK), viewed_at. (Feeds recently-viewed and analytics.)

**search_history** — id (PK), user_id (FK, nullable), session_token, query, result_count, created_at.

**audit_logs** — id (PK), actor_id (FK users), action, entity_type, entity_id, before (jsonb), after (jsonb), ip_address, created_at.

**ERD relationship summary:** `users 1—N addresses`; `users 1—N orders`; `categories 1—N products` (self-referencing for subcategories); `products 1—N product_variants`; `product_variants 1—1 inventory`; `product_variants 1—N product_images`; `orders 1—N order_items`; `orders 1—N order_status_history`; `orders 1—1..N payments`; `payments 1—N refunds`; `orders 1—1 shipments`; `orders N—1 coupons` (via coupon_usages for audit); `products 1—N reviews`; `users 1—N reviews`; `users/roles N—N permissions` via `role_permissions`; `wilayas 1—N communes`; `communes 1—N addresses`.

---

## 13. API Architecture (REST)

All endpoints prefixed `/api/v1`. Auth via Bearer JWT (customer/admin) except public catalog reads. All responses JSON with a consistent envelope: `{ success, data, error }`. Errors return standard HTTP codes with a machine-readable `error.code` and localized `error.message`.

| Resource | Endpoint | Method | Auth | Notes |
|---|---|---|---|---|
| Auth | `/auth/register` | POST | None | Phone/email + password; validates Algerian phone format |
| Auth | `/auth/login` | POST | None | Returns JWT + refresh token |
| Auth | `/auth/otp/send` | POST | None | Rate-limited (1/min per phone) |
| Auth | `/auth/otp/verify` | POST | None | Max 5 attempts, 5-min expiry |
| Auth | `/auth/password/reset` | POST | None | Sends OTP/link |
| Auth | `/auth/google` | POST | None | OAuth token exchange |
| Users | `/users/me` | GET/PATCH | Customer | Profile read/update |
| Addresses | `/users/me/addresses` | GET/POST/PATCH/DELETE | Customer | CRUD, enforces wilaya/commune validity |
| Products | `/products` | GET | None | Filters: category, price, color, size, rating, in_stock, sort, page |
| Products | `/products/{slug}` | GET | None | Full PDP payload incl. variants, reviews summary |
| Categories | `/categories` | GET | None | Tree structure, up to 3 levels |
| Search | `/search?q=` | GET | None | Autocomplete + full results, typo-tolerant, multilingual |
| Cart | `/cart` | GET | Optional | Guest via session token header |
| Cart | `/cart/items` | POST/PATCH/DELETE | Optional | Stock-validated on write |
| Cart | `/cart/coupon` | POST/DELETE | Optional | Validates coupon eligibility |
| Checkout | `/checkout` | POST | Optional | Creates order from cart; validates stock + address + delivery fee atomically |
| Orders | `/orders` | GET | Customer | Order history |
| Orders | `/orders/{order_number}` | GET | Customer or guest+phone | Order detail/tracking |
| Orders | `/orders/{id}/cancel` | POST | Customer | Only allowed pre-`Processing` |
| Payments | `/payments/{order_id}/initiate` | POST | Customer/guest | Returns provider redirect/session for online methods |
| Payments | `/payments/webhook/{provider}` | POST | Signed webhook | Signature-verified, idempotent |
| Reviews | `/products/{id}/reviews` | GET/POST | Optional (POST requires auth) | POST requires prior delivered order for verified badge |
| Wishlist | `/wishlist` | GET/POST/DELETE | Optional | Merges guest — account on login |
| Coupons | `/coupons/validate` | POST | Optional | Real-time validation against cart |
| Notifications | `/notifications` | GET/PATCH | Customer | In-app inbox, mark read |
| Admin — Products | `/admin/products` | GET/POST/PATCH/DELETE | Admin (Product Manager+) | Full CRUD incl. variants/images |
| Admin — Orders | `/admin/orders` | GET/PATCH | Admin (Order Manager+) | Status updates, notes |
| Admin — Inventory | `/admin/inventory/{variant_id}/adjust` | POST | Admin (Inventory Manager+) | Creates audit trail entry |
| Admin — Customers | `/admin/customers` | GET/PATCH | Admin (Support+) | View/block, order history |
| Admin — Coupons | `/admin/coupons` | GET/POST/PATCH/DELETE | Admin (Marketing+) | — |
| Admin — Reports | `/admin/reports/sales` | GET | Admin (Finance+) | Revenue, AOV, sales by wilaya |
| Admin — Delivery | `/admin/shipments` | GET/PATCH | Admin (Order Manager+) | Courier status sync |

Validation: all write endpoints validate against a JSON schema server-side regardless of client-side checks; phone fields validated against Algerian format; monetary fields validated as positive integers. Error responses follow `{ success: false, error: { code, message, field_errors? } }` with 400 (validation), 401 (unauthenticated), 403 (unauthorized), 404, 409 (conflict, e.g., stock), 422 (business rule violation), 429 (rate limited), 500.

---

## 14. Technical Architecture

| Layer | Recommendation | Why |
|---|---|---|
| Frontend | React / Next.js | SSR/ISR for fast first paint and SEO on product/category pages, built-in i18n routing for ar/fr/en, large ecosystem, easy path to a future React Native app sharing logic |
| Backend | Node.js (NestJS) or Laravel | Both offer strong structure (modules/controllers), mature ORM, and fast hiring pool in the Algerian/regional market; Node.js chosen if the team prefers a unified JS/TS stack with the Next.js frontend, Laravel if the team has strong PHP expertise — either satisfies the architecture below |
| Database | PostgreSQL | Strong relational integrity for orders/payments/inventory, native full-text search (with Arabic-friendly config via `unaccent`/trigram extensions) usable pre-Elasticsearch, JSONB for flexible fields (webhook payloads, audit diffs) |
| Cache | Redis | Session storage, cart caching for guests, rate limiting, hot product/category caching, queue backend (jobs: notifications, webhook retries, reconciliation) |
| Storage | S3-compatible object storage | Product images, review photos; served via CDN |
| Search | PostgreSQL full-text (`pg_trgm` + `unaccent`) at MVP; migrate to Meilisearch or OpenSearch when catalog size/typo-tolerance/relevance needs exceed Postgres capability | Avoids operating a separate search cluster before it's needed, while keeping a clean migration path |
| CDN | CloudFront/Cloudflare-class CDN in front of static assets and images | Reduces latency for Algerian users on variable mobile networks |
| Queue/Jobs | Redis-backed queue (BullMQ / Laravel Queues) | Async notification sending, webhook processing, reconciliation jobs |

**Scalability path:** stateless API layer behind a load balancer allows horizontal scaling; the payment provider abstraction (Section 7.9) allows new gateways without checkout rewrites; the product/variant/inventory model already supports a future `warehouse_id` on inventory rows for multi-warehouse; the `products` table's single-vendor assumption (Section 2, A1) can extend to multi-vendor by adding a `vendor_id` FK without restructuring orders; locale tables (`wilayas`) can be paralleled with a `countries` table for cross-border expansion; a recommendation engine can consume the existing `product_views`/`search_history`/`orders` tables as its training data source without new instrumentation.

---

## 15. Security

| Area | Requirement |
|---|---|
| Authentication | JWT access + refresh tokens; short-lived access tokens (15 min), rotated refresh tokens |
| Authorization | RBAC enforced server-side on every admin endpoint, never client-side only |
| Password security | bcrypt/argon2 hashing, min 8 chars, breach-list check on registration |
| OTP security | Rate-limited (1/min), max 5 verify attempts, 5-minute expiry, server-side generation only |
| Rate limiting | Per-IP and per-account limits on auth, OTP, checkout, and search endpoints |
| CSRF | Anti-CSRF tokens on all state-changing browser-form submissions |
| XSS | Output encoding, CSP headers, sanitized rich-text fields (product descriptions, reviews) |
| SQL injection | Parameterized queries/ORM only, no raw string concatenation |
| Input validation | Server-side schema validation on every write endpoint |
| File upload security | Type/size validation, virus scan on review/product image uploads, served from a separate asset domain |
| Payment handling | No card data touches CHABA servers (hosted fields/redirect to CIB/Edahabia); PCI scope minimized |
| Webhook verification | HMAC signature verification, replay protection via idempotency keys |
| Session security | Secure, HttpOnly, SameSite cookies where applicable; token revocation on logout/password change |
| Audit logs | All admin writes logged with before/after state, actor, IP, timestamp |
| Admin security | Mandatory 2FA for Admin/Super Admin, IP allow-list optional, forced session timeout |
| Backup strategy | Automated daily encrypted DB backups, 30-day retention minimum, periodic restore drills |

---

## 16. Performance

| Metric | Target |
|---|---|
| Largest Contentful Paint (LCP) | < 2.5s on mid-range Android, 4G |
| First Input Delay / INP | < 200ms |
| Cumulative Layout Shift (CLS) | < 0.1 |
| API p95 (read) | < 300ms |
| API p95 (checkout write) | < 800ms |
| Image optimization | WebP/AVIF with responsive `srcset`, lazy loading below the fold |
| Caching | CDN-cached static assets and images; Redis-cached hot catalog/category queries with short TTL + invalidation on write |
| Database indexing | Indexes on all FK columns, `slug`, `sku`, `order_number`, `phone`, plus composite indexes for common filter/sort combinations |
| Mobile performance budget | < 300KB JS on first load for catalog/PDP routes (code-split, route-based) |

---

## 17. Analytics

**Events tracked:** `page_view`, `product_view`, `add_to_cart`, `remove_from_cart`, `checkout_started`, `checkout_step_completed` (per step), `checkout_completed`, `payment_method_selected`, `coupon_applied`, `search_performed`, `search_no_results`, `wishlist_add`, `review_submitted`, `order_cancelled`, `order_returned`.

**KPIs derived:** conversion rate (checkout_completed / product_view), cart abandonment rate, average order value, customer lifetime value, repeat purchase rate, best-selling products/categories, sales by wilaya, cancellation rate, return rate, COD refusal rate, delivery success rate by courier partner. Events are sent to a product analytics platform (e.g., a GA4/Mixpanel/PostHog-class tool) with PII minimized in event payloads (order/customer IDs, not raw phone numbers).

---

## 18. SEO

SEO-friendly URLs (`/products/{slug}`, `/categories/{slug}`), unique meta title/description per product and category in each language, Open Graph tags for social sharing, JSON-LD structured data (Product, Offer, AggregateRating, BreadcrumbList schemas), visible breadcrumbs on all catalog/PDP pages, `sitemap.xml` auto-generated and split by locale, `robots.txt` configured to allow catalog crawling and disallow cart/checkout/account, canonical URLs per locale with `hreflang` tags (ar/fr/en) to avoid duplicate-content penalties across languages, Arabic SEO handled via correctly declared `lang="ar" dir="rtl"` and Arabic slugs/transliteration strategy, French/English SEO via standard localized metadata.

---

## 19. Admin Dashboard

**Dashboard home:** revenue (today/week/month, trend chart), orders count and status breakdown, new vs. returning customers, low-stock alerts, average order value, conversion rate, best-selling products, sales-by-wilaya table/heatmap, sales charts over time.

**Product Management:** create/edit/delete products, manage categories/subcategories assignment, manage variants (color/size/SKU/price/stock), manage images (upload, reorder, alt text), pricing (base price, compare-at price for discount %), SEO fields (title/description/slug per language), product status (draft/active/archived).

**Category Management:** create/edit/delete categories up to 3 levels, reorder, assign images, activate/deactivate.

**Order Management:** searchable/filterable order list (status, date, wilaya, payment method), order detail view (items, customer, address, payment, shipment, notes, full status history), manual status update, COD confirmation action, cancellation with reason, refund initiation.

**Customer Management:** customer list/search, profile view with full order history, block/unblock, COD-refusal flag, support notes.

**Inventory Management:** stock levels per variant, reserved vs. available, manual stock adjustment with reason (creates audit entry), low-stock threshold configuration, adjustment history.

**Coupon Management:** create/edit/deactivate coupons, usage stats, per-customer targeting.

**Promotions:** create/schedule product/category/cart-level promotions, active promotion overview.

**Reviews:** moderation queue (approve/reject), reported reviews queue, per-product review overview.

**Delivery Management:** courier partner configuration, wilaya fee/ETA table management, shipment status list, failed-delivery queue.

**Payment Management:** transaction list, reconciliation view (COD remittance vs. delivered orders, online settlement vs. captured payments), refund processing, discrepancy flags.

**Reports:** sales, inventory, customer, delivery performance, coupon usage — filterable by date range, exportable (CSV).

**Notifications (admin):** template management for SMS/email/WhatsApp events, delivery/failure logs.

**Settings:** general store settings, language/currency config, wilaya delivery fee table, payment provider configuration, tax settings, legal pages (Terms/Privacy/Shipping/Return policy content).

### 19.1 Admin Roles (RBAC)

| Role | Key permissions |
|---|---|
| Super Admin | Full access to all modules including Settings, Roles & Permissions, and payment provider configuration |
| Admin | Full access except Roles & Permissions and payment provider secrets |
| Order Manager | Orders (full), Delivery (full), Customers (view), Inventory (view) |
| Product Manager | Products (full), Categories (full), Inventory (view), Reviews (moderate) |
| Inventory Manager | Inventory (full incl. adjustments), Products (view) |
| Customer Support | Orders (view + status update + notes), Customers (full), Notifications (view), Reviews (view) |
| Marketing Manager | Coupons (full), Promotions (full), Reports — sales/coupon (view), Notifications templates (full) |
| Finance Manager | Payments (full), Reports — all (view/export), Reconciliation (full) |

---

## 20. Business Rules

| Domain | Rule |
|---|---|
| Product availability | A product/variant is purchasable only if `available_quantity > 0` and `status = active` |
| Inventory reservation | Adding to cart reserves stock for 30 minutes (guest) / until order submission (logged-in checkout in progress); reservation released if cart abandoned or checkout not completed within the window |
| Order cancellation | Customer-initiated cancellation allowed only while order is `Pending` or `Confirmed`; once `Processing` or later, cancellation must go through Support |
| Refunds | Online payments refunded via provider API; COD "refunds" are store-credit or bank transfer since cash was never digitally captured |
| Returns | Accepted within a configurable window (e.g., 7 days) per the Return Policy; requires item in original condition; restocked into inventory after inspection |
| Coupons | One coupon per order unless explicitly marked stackable; invalid/expired/usage-exceeded coupons are rejected with a clear reason at validation, not silently ignored |
| Discounts | Product-level and cart-level discounts do not stack unless explicitly configured; the system always applies the best single discount for the customer unless stacking is enabled |
| COD | Orders above the configurable COD threshold require confirmation before shipping; repeat refusals may restrict the customer to prepaid-only |
| Delivery fees | Always computed server-side from the wilaya/commune fee table at order creation time; client-displayed estimate is advisory until order submission locks it in |
| Minimum order value | Enforced only where a coupon/promotion requires it; no platform-wide minimum order value at MVP |
| Reviews | Only customers with a `Delivered` order for that product may leave a "Verified Purchase" review; anyone may leave a non-verified review if public reviews are enabled |
| Customer accounts | Phone number is the unique identity; one account per phone number |
| Guest checkout | Always available; guest orders are trackable via order number + phone match; guest can convert to account post-purchase without losing order history (matched by phone) |
| Abandoned carts | Carts inactive for 24h+ with items trigger an optional reminder notification (V1); reserved stock is released after the reservation window regardless of notification |

---

## 21. Edge Cases

| # | Edge case | Expected system behavior |
|---|---|---|
| 1 | Product goes out of stock during checkout | Checkout blocked on that item at final validation; customer shown which item is unavailable, offered to remove it or save for later |
| 2 | Payment succeeds but order creation fails | Payment is held in `authorized`/`captured` state with no linked order; async reconciliation job detects orphaned payment and auto-creates the order or flags for manual resolution; customer never loses funds |
| 3 | Customer closes browser during online payment | Order remains `Pending`/payment `processing`; webhook from provider (async) finalizes status; if no webhook arrives within timeout, status resolved via provider status-check job |
| 4 | Duplicate payment webhook | Deduplicated via idempotency key (provider transaction ID); second delivery is a no-op |
| 5 | Duplicate order (double submit) | Checkout endpoint is idempotent via a client-generated idempotency token; duplicate submissions within the window return the original order |
| 6 | Invalid coupon code | Rejected at validation with a specific reason ("code not found") |
| 7 | Expired coupon | Rejected with "this coupon has expired" |
| 8 | Coupon usage limit reached | Rejected with "this coupon is no longer available" |
| 9 | Delivery address outside delivery zone | Checkout blocked at address step with a clear message; customer offered stop-desk pickup if available in that wilaya |
| 10 | Customer rejects COD order at door | Courier marks `Failed — Refused`; order moves to `Returned`; customer flagged for COD-refusal tracking |
| 11 | Product price changes while in cart | Cart displays current price at checkout with a "price updated" notice if different from when added; customer must acknowledge before proceeding |
| 12 | Network failure mid-checkout | Form state preserved client-side; retry does not create a duplicate order (idempotency token) |
| 13 | Image upload failure (admin) | Upload retried automatically once; on repeated failure, admin sees explicit error, product save is not blocked by image failure alone |
| 14 | Payment timeout | Transaction marked `expired` after provider-defined window; customer prompted to retry payment or switch to COD |
| 15 | Two customers buy the last unit simultaneously | Inventory reservation uses row-level locking/atomic decrement; second customer's checkout fails fast with an out-of-stock message |
| 16 | Guest checkout with a phone number already tied to an account | Order is created as guest but linked to the existing account by phone match for order history purposes; no password prompt during checkout |
| 17 | OTP requested repeatedly (abuse) | Rate-limited to 1 per minute per phone, max 5 per hour |
| 18 | Wrong OTP entered repeatedly | Locked after 5 attempts for 15 minutes |
| 19 | Coupon combined with an already-discounted product | System applies configured stacking rule; if not stackable, best single discount wins and customer is informed which discount was applied |
| 20 | Customer cancels an order already `Shipped` | Cancellation blocked in-app; customer redirected to contact Support, who can initiate a return-to-sender if the courier allows |
| 21 | Refund requested on a COD order that was never collected | System rejects — no payment exists to refund; if item was returned, inventory is restocked and order marked `Returned` with no monetary refund needed |
| 22 | Partial return (some items from a multi-item order) | Order split at the item level for return/refund purposes; remaining items keep their status |
| 23 | Review submitted for a product never purchased | Allowed as a non-verified review if public reviews are permitted; otherwise blocked with "you can review after purchase" |
| 24 | Admin deletes a category with active products | Deletion blocked; admin must reassign products first, or category is soft-archived instead of hard-deleted |
| 25 | Wilaya/commune address entered for a delivery-restricted area (temporary courier outage) | Wilaya is temporarily marked unavailable in admin; checkout shows "currently unavailable in this area" instead of a broken fee calculation |
| 26 | Customer's saved address commune no longer exists (data change) | Address flagged invalid on next use; customer prompted to re-select before checkout completes |
| 27 | Search query is empty or only whitespace | Returns popular products/categories fallback, not an empty error page |
| 28 | Product has variants but customer adds to cart without selecting one | Add-to-cart blocked client- and server-side until a valid variant is selected |
| 29 | Currency/price displayed with rounding mismatch between cart and PDP | All monetary values computed and stored as integer centimes server-side; UI always formats from the authoritative server value, never recalculates independently |
| 30 | Courier marks delivered but customer disputes non-receipt | Order flagged for Support investigation; courier proof-of-delivery (if available) attached to the order record for resolution |
| 31 | Admin user demoted/role changed while logged in | Active session permissions re-checked server-side on next request (not cached client-side), so access is revoked immediately |
| 32 | Bulk product import contains invalid rows | Valid rows imported, invalid rows rejected with a per-row error report; no partial/corrupt product records created |
| 33 | Webhook received from an unverified/spoofed source | Signature verification fails, request rejected and logged as a security event, no state change occurs |

---

## 22. User Stories & Acceptance Criteria

**Authentication**
As a shopper, I want to check out without creating an account, so that I can buy quickly.
- Given I have items in my cart, when I proceed to checkout without logging in, then I can complete the order as a guest using just my name, phone, and address.

As a shopper, I want to verify my phone with an OTP, so that my account is secure.
- Given I entered a valid phone number, when I request an OTP, then I receive a 4—6 digit code within 60 seconds that expires after 5 minutes.

**Catalog & Search**
As a shopper, I want to filter products by size and color, so that I only see relevant items.
- Given I am on a category page, when I select a size filter, then only products with a matching in-stock variant are shown.

As an Arabic-speaking shopper, I want search to understand my Arabic queries even with typos, so that I can find products without knowing exact spelling.
- Given I search "قميص" or a close misspelling, when results load, then relevant shirt products are returned ranked by relevance.

**Cart & Checkout**
As a shopper, I want to see the total delivery cost for my wilaya before paying, so that there are no surprises.
- Given I select my wilaya during checkout, when the delivery method is chosen, then the exact delivery fee and ETA are displayed before I confirm payment.

As a shopper, I want to pay cash on delivery, so that I can pay when I physically receive my order.
- Given I select Cash on Delivery at checkout, when I submit my order, then the order is created with `payment_method = cod` and `payment_status = cod_pending_collection`, and no payment is charged upfront.

**Order Tracking**
As a returning customer, I want to track my order status, so that I know when it will arrive.
- Given I have a confirmed order, when I open Order Tracking, then I see a timeline reflecting the current status and the latest courier update.

**Admin**
As an Inventory Manager, I want to adjust stock with a reason, so that inventory stays accurate and auditable.
- Given I adjust a variant's stock quantity, when I save the change, then an `inventory_adjustments` record is created with my user ID, the delta, and the reason, and `available_quantity` updates immediately.

As an Order Manager, I want to confirm a COD order by phone, so that refusal risk is reduced before shipping.
- Given a COD order exceeds the confirmation threshold, when I mark it confirmed after a successful call, then its status moves from `Pending` to `Confirmed` and it becomes eligible for processing.

*(Additional user stories following this Given/When/Then pattern cover: wishlist add/remove, coupon application, review submission with verified-purchase badge, admin product creation with variants, refund processing, and low-stock alerting — each derived directly from the corresponding functional requirement in Section 7 and Section 19.)*

---

## 23. Acceptance Criteria (Product-Level)

The platform is not production-ready until all of the following are true: customers can discover products via homepage/categories/search; search returns relevant, typo-tolerant results in Arabic, French, and English; products can be purchased end-to-end; cart correctly reflects stock and pricing at all times; checkout completes successfully for both guest and logged-in customers; Cash on Delivery works end-to-end including confirmation and reconciliation; the online-payment architecture is implemented and at least one gateway (CIB or Edahabia) is live or stubbed with a clear activation path; orders can be fully managed from the admin dashboard (view, update status, cancel, refund); inventory is synchronized in real time and overselling is prevented; customers can track orders via a status timeline; admins can create/edit/manage products, categories, and variants; notification events (order confirmation, status changes, OTP) are delivered reliably; the security requirements in Section 15 are implemented; the mobile experience meets the performance targets in Section 16; the Arabic RTL experience is fully polished — not a mirrored afterthought — across every customer-facing screen.

---

## 24. MVP Scope

**MVP (P0 — required for launch):** authentication (register/login/OTP/password reset/guest checkout); homepage with hero, categories, featured/best-seller/new-arrival rails; catalog with filters/sort/pagination; search with autocomplete and Arabic/French/English support; PDP with variants, stock, delivery estimate, reviews display; cart with stock validation and persistence; full checkout (guest + logged-in, wilaya/commune address, delivery method, COD); order lifecycle management in admin; wilaya-based delivery fee table; COD workflow including confirmation and reconciliation; customer account (profile, addresses, orders, wishlist); reviews (submission + moderation); coupons (percentage/fixed/free shipping); basic notifications (SMS + email for OTP and order events); admin dashboard (products, categories, orders, customers, inventory, coupons, basic reports); RBAC with the 8 defined roles; core security (auth, RBAC, input validation, rate limiting, HTTPS, hashed passwords); SEO fundamentals (slugs, metadata, sitemap).

**Explicitly NOT in MVP (to avoid unnecessary complexity):** online payment gateway going fully live (architecture ready, activation can follow immediately after launch); Google authentication; WhatsApp notifications; price-drop and back-in-stock wishlist alerts; Buy X Get Y and customer-specific targeted coupons (basic coupon types only); personalized ML-driven recommendations (use simple "recently viewed" + "best sellers" instead); multi-warehouse support; native mobile apps; multi-vendor marketplace; advanced analytics dashboards beyond core KPIs; Elasticsearch/Meilisearch (Postgres search is sufficient at MVP catalog size).

**V1 (first major update):** Google authentication; WhatsApp notifications; online payment gateway fully live; wishlist price-drop/back-in-stock alerts; advanced coupon types (Buy X Get Y, customer-specific); abandoned cart reminders; expanded admin reports and export; stop-desk/pickup delivery option; personalized recommendations (rules-based).

**V2 (advanced):** multi-vendor marketplace; multi-warehouse inventory; native mobile apps (iOS/Android); ML-based recommendation engine; Elasticsearch/Meilisearch migration; cross-border/multi-country expansion; loyalty/points program; advanced fraud detection for COD.

---

## 25—26. Development Roadmap

| Phase | Focus | Key tasks | Dependencies | Deliverables | Priority | Complexity |
|---|---|---|---|---|---|---|
| 1 — Foundation | Infra & architecture | Repo setup, CI/CD, DB schema migration, environment provisioning, design system tokens | None | Running skeleton app, deployed staging env | P0 | Medium |
| 2 — Authentication | Identity | Register/login, OTP (SMS provider integration), password reset, guest sessions, RBAC scaffolding | Phase 1 | Working auth for customers + admin | P0 | Medium |
| 3 — Catalog | Products & browsing | Product/category/variant models, homepage, category pages, PDP, search (Postgres FTS) | Phase 1 | Browsable catalog with search | P0 | High |
| 4 — Cart & Checkout | Purchase flow | Cart persistence, stock reservation, checkout steps, address model (wilaya/commune), delivery fee engine | Phases 2, 3 | End-to-end checkout to order creation | P0 | High |
| 5 — Orders & Delivery | Fulfillment | Order lifecycle, status history, courier integration, shipment tracking, COD workflow | Phase 4 | Orders trackable from creation to delivery | P0 | High |
| 6 — Payments | Payment architecture | Payment abstraction layer, COD provider, CIB/Edahabia integration, webhook handling, reconciliation | Phase 4 | Functional COD + online payment path | P0 | High |
| 7 — Admin Dashboard | Operations | Dashboard KPIs, product/order/customer/inventory/coupon management, RBAC enforcement | Phases 3—6 | Fully operable back office | P0 | High |
| 8 — Testing | QA | Unit, integration, E2E, security, performance, localization/RTL testing | Phases 1—7 | Test suite green, bug backlog triaged | P0 | Medium |
| 9 — Deployment | Release engineering | Production infra, monitoring/alerting, backup strategy, load testing | Phase 8 | Production-ready environment | P0 | Medium |
| 10 — Launch | Go-live | Final QA pass, content population, soft launch, monitoring | Phase 9 | Public launch | P0 | Low |

---

## 27. QA / Testing Strategy

Unit tests (business logic: pricing, discount calculation, inventory reservation, delivery fee lookup); integration tests (API endpoints against a test DB, including error/edge paths from Section 21); API contract tests; E2E tests (Playwright/Cypress covering full guest and logged-in checkout, COD and online payment paths, admin order management); payment tests (sandbox CIB/Edahabia transactions, webhook simulation, idempotency/duplicate-webhook tests); security tests (auth bypass attempts, RBAC boundary tests, rate-limit verification, OWASP scan); performance tests (load testing checkout under concurrent traffic, catalog under high read volume); responsive tests (mobile/tablet/desktop breakpoints on real devices); RTL tests (layout mirroring, icon direction, text alignment across every screen in Arabic); localization tests (content correctness and completeness across ar/fr/en, including pluralization and date/number formatting).

**Critical test scenarios:** concurrent purchase of last-unit inventory; COD order full lifecycle including refusal and return; coupon edge cases (expired, exceeded, invalid); guest-to-account order linking by phone; webhook duplicate/replay handling; Arabic search with typos and mixed-script queries; full RTL visual regression across homepage, PDP, cart, checkout, and admin dashboard.

---

## 28. Risks

| Risk | Impact | Mitigation |
|---|---|---|
| High COD refusal rate erodes margins | Financial | Confirmation-call workflow above threshold, refusal tracking, gradual push toward prepaid incentives |
| Online payment gateway onboarding delays (CIB/Edahabia approval) | Schedule | Architecture supports COD-only launch with payment layer ready to activate without rework |
| Courier partner delivery reliability varies by wilaya, especially remote south | Customer trust | Transparent per-wilaya ETA, proactive status updates, fallback stop-desk option |
| Arabic RTL quality gaps if treated as secondary in development | Brand/UX | RTL-first design system and RTL as a mandatory QA gate, not an afterthought |
| Overselling due to race conditions at high traffic | Operational/trust | Atomic stock reservation with row-level locking, tested under concurrent load |
| Data security incident involving customer phone/address data | Legal/trust | Encryption at rest/in transit, RBAC, audit logs, regular security review |

---

## 29. Future Scalability

The architecture (Section 14) is deliberately structured so that: a `vendor_id` can be added to `products` to enable a multi-vendor marketplace without restructuring orders/payments; a `warehouse_id` can be added to `inventory` for multi-warehouse stock without restructuring products; the payment provider abstraction (Section 7.9) allows new gateways or a mobile-app payment SDK without checkout rewrites; the locale/location model (`wilayas`/`communes`) can be paralleled with a `countries` table for cross-border expansion; Next.js frontend logic can share business rules with a future React Native mobile app; Postgres full-text search can be swapped for Meilisearch/OpenSearch behind the same search API contract once catalog scale requires it; `product_views`/`search_history`/`orders` already capture the behavioral data a future recommendation engine needs.

---

## 30. Final Launch Checklist

- [ ] All MVP features (Section 24) implemented and passing QA (Section 27)
- [ ] Arabic RTL, French, and English fully polished across every screen
- [ ] COD workflow tested end-to-end including refusal and return paths
- [ ] Delivery fee table populated and verified for all 58 wilayas
- [ ] Payment abstraction layer live for COD; online gateway integration tested in sandbox at minimum
- [ ] Security checklist (Section 15) fully implemented and reviewed
- [ ] Performance targets (Section 16) met on real mobile devices
- [ ] Admin RBAC roles configured and access-tested per role
- [ ] Notification events (SMS/email/OTP) verified in production
- [ ] SEO fundamentals live (sitemap, metadata, structured data, hreflang)
- [ ] Backup and monitoring/alerting in place
- [ ] Legal pages (Terms, Privacy, Shipping Policy, Return Policy) published in all three languages
- [ ] Soft launch with a limited audience before full public launch

---

## Appendices

### Appendix A — Final MVP Feature List
Authentication (register, login, OTP, password reset, guest checkout) · Homepage (hero, categories, best sellers, new arrivals, discounts, trust indicators, recently viewed) · Catalog (categories, filters, sort, pagination, badges) · Search (autocomplete, AR/FR/EN, typo tolerance) · PDP (gallery, variants, stock, delivery estimate, reviews, related products) · Cart (stock validation, persistence, coupon) · Checkout (guest + account, wilaya/commune address, delivery method, COD) · Order management (full lifecycle, status history) · Wilaya-based delivery fees · COD workflow (confirmation, delivery, reconciliation) · Customer account (profile, addresses, orders, wishlist) · Reviews (submission, moderation, verified purchase) · Coupons (percentage, fixed, free shipping) · Notifications (SMS + email for core events) · Admin dashboard (products, categories, orders, customers, inventory, coupons, core reports) · RBAC (8 roles) · Core security · SEO fundamentals.

### Appendix B — Complete Sitemap
**Customer:** Homepage, Categories, Category Details, Search, Search Results, Product Details, Cart, Checkout, Order Confirmation, Order Tracking, Login, Register, OTP, Forgot Password, Account (Profile, Orders, Order Details, Wishlist, Addresses, Reviews, Notifications, Settings), About, Contact, FAQ, Terms, Privacy, Shipping Policy, Return Policy.
**Admin:** Dashboard, Products, Categories, Orders, Customers, Inventory, Coupons, Promotions, Reviews, Delivery, Payments, Reports, Notifications, Settings, Roles & Permissions.

### Appendix C — Complete Database Entities List
users, roles, permissions, role_permissions, addresses, wilayas, communes, categories, products, product_variants, product_images, inventory, inventory_adjustments, carts, cart_items, wishlists, orders, order_items, order_status_history, payments, refunds, shipments, coupons, coupon_usages, promotions, reviews, review_images, review_reports, notifications, product_views, search_history, audit_logs.

### Appendix D — Complete API Endpoint List
`/auth/register`, `/auth/login`, `/auth/otp/send`, `/auth/otp/verify`, `/auth/password/reset`, `/auth/google`, `/users/me`, `/users/me/addresses`, `/products`, `/products/{slug}`, `/categories`, `/search`, `/cart`, `/cart/items`, `/cart/coupon`, `/checkout`, `/orders`, `/orders/{order_number}`, `/orders/{id}/cancel`, `/payments/{order_id}/initiate`, `/payments/webhook/{provider}`, `/products/{id}/reviews`, `/wishlist`, `/coupons/validate`, `/notifications`, `/admin/products`, `/admin/orders`, `/admin/inventory/{variant_id}/adjust`, `/admin/customers`, `/admin/coupons`, `/admin/reports/sales`, `/admin/shipments`.

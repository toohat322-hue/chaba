<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\DeliveryFeeController as AdminDeliveryFeeController;
use App\Http\Controllers\Api\V1\Admin\FooterColumnController as AdminFooterColumnController;
use App\Http\Controllers\Api\V1\Admin\FooterFeatureController as AdminFooterFeatureController;
use App\Http\Controllers\Api\V1\Admin\FooterLinkController as AdminFooterLinkController;
use App\Http\Controllers\Api\V1\Admin\FooterPaymentMethodController as AdminFooterPaymentMethodController;
use App\Http\Controllers\Api\V1\Admin\FooterSocialLinkController as AdminFooterSocialLinkController;
use App\Http\Controllers\Api\V1\Admin\HeroSlideController as AdminHeroSlideController;
use App\Http\Controllers\Api\V1\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Api\V1\Admin\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RefreshController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialCallbackController;
use App\Http\Controllers\Api\V1\Auth\SocialExchangeController;
use App\Http\Controllers\Api\V1\Auth\SocialRedirectController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorLoginController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CommuneController;
use App\Http\Controllers\Api\V1\DeliveryFeeController;
use App\Http\Controllers\Api\V1\FooterController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\HeroSlideController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderTrackController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RedirectController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ReviewImageController;
use App\Http\Controllers\Api\V1\ReviewReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WilayaController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

// PRD §13: all endpoints are prefixed /api/v1. Feature routes (auth, products,
// cart, checkout, admin, ...) are added phase by phase on top of this file.
Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class);

    // PRD §15: rate limiting on auth/OTP endpoints, per-IP via Laravel's
    // throttle middleware (in addition to OtpService's own per-phone limits).
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', RegisterController::class)->middleware('throttle:5,1');
        Route::post('/login', LoginController::class)->middleware('throttle:10,1');
        Route::post('/otp/send', [OtpController::class, 'send'])->middleware('throttle:5,1');
        Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1');
        Route::post('/password/reset', PasswordResetController::class)->middleware('throttle:5,1');

        // Social login (Google/Facebook/Apple). Both redirect and callback
        // are reached by full-page browser navigation, never fetch — every
        // path through the callback ends in a redirect back to the
        // frontend, never raw JSON (see SocialCallbackController). POST is
        // required on the callback for Apple's form_post response mode.
        Route::get('/{provider}/redirect', SocialRedirectController::class)
            ->whereIn('provider', ['google', 'facebook', 'apple'])
            ->middleware('throttle:20,1');
        Route::match(['get', 'post'], '/{provider}/callback', SocialCallbackController::class)
            ->whereIn('provider', ['google', 'facebook', 'apple'])
            ->middleware('throttle:20,1');
        Route::post('/social/exchange', SocialExchangeController::class)->middleware('throttle:10,1');

        // Second step of a 2FA login (see LoginController) — authenticated
        // with the short-lived pending token, not a normal access token.
        Route::post('/2fa/verify', TwoFactorLoginController::class)
            ->middleware(['auth:sanctum', 'abilities:two-factor-pending', 'throttle:10,1']);

        Route::middleware(['auth:sanctum', 'abilities:access-api'])->group(function (): void {
            Route::post('/logout', LogoutController::class);
        });

        Route::post('/refresh', RefreshController::class)->middleware(['auth:sanctum', 'abilities:refresh-token']);
    });

    // Wilaya/commune/delivery-fee reads and guest order tracking: public, no
    // auth required. Registered before the /orders/{order} route below so
    // this exact path always wins over that wildcard.
    Route::get('/wilayas', [WilayaController::class, 'index']);
    Route::get('/communes', [CommuneController::class, 'index']);
    Route::get('/delivery-fees/{wilaya_code}', [DeliveryFeeController::class, 'show']);
    // Order numbers are a plain incrementing sequence (CHB-{year}-{seq}), so
    // this must be throttled like every other public lookup/write endpoint
    // above — otherwise it's an unbounded enumeration surface for guessing a
    // phone number's orders.
    Route::get('/orders/track', [OrderTrackController::class, 'show'])->middleware('throttle:10,1');

    // Checkout: same guest/auth duality as Cart (no auth:sanctum requirement
    // — CartService::resolveCart handles both via X-Guest-Session or token).
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1');

    // Best-effort "the browser attempted the wa.me redirect" signal — public
    // (guest checkout has no session to authenticate), guarded by the same
    // order_number+phone match as /orders/track above.
    Route::post('/checkout/{order_number}/whatsapp-opened', [CheckoutController::class, 'whatsappOpened'])
        ->middleware('throttle:20,1');

    // Payment gateway webhooks: server-to-server, no bearer auth possible
    // (see PaymentWebhookController). Generous but present throttle — real
    // gateway retries shouldn't be dropped, but the endpoint isn't unlimited.
    Route::post('/webhooks/payments/{gateway}', PaymentWebhookController::class)->middleware('throttle:60,1');

    // General authenticated endpoints: generous but present throttle (same
    // rationale as the webhook route above) — nothing here was rate limited
    // before, so a single compromised/buggy client could hammer the DB
    // unbounded.
    Route::middleware(['auth:sanctum', 'abilities:access-api', 'throttle:120,1'])->group(function (): void {
        Route::get('/users/me', [UserController::class, 'show']);
        Route::patch('/users/me', [UserController::class, 'update']);
        Route::patch('/users/me/password', [UserController::class, 'updatePassword']);
        Route::post('/users/me/password/set', [UserController::class, 'setPassword']);

        // Connected Accounts (My Account -> Security) — linking reuses the
        // same anonymous /auth/{provider}/redirect flow via a short-lived
        // ticket (see SocialLinkTicketService) rather than a separate
        // authenticated OAuth path, since a full-page navigation can't carry
        // a Bearer header.
        Route::get('/users/me/social-accounts', [SocialAccountController::class, 'index']);
        Route::post('/users/me/social-accounts/{provider}/link-token', [SocialAccountController::class, 'linkToken']);
        Route::delete('/users/me/social-accounts/{provider}', [SocialAccountController::class, 'destroy']);

        Route::post('/cart/merge', [CartController::class, 'merge']);

        Route::post('/users/me/2fa', [TwoFactorController::class, 'store']);
        Route::post('/users/me/2fa/confirm', [TwoFactorController::class, 'confirm']);
        Route::delete('/users/me/2fa', [TwoFactorController::class, 'destroy']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::patch('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);

        // /notifications/read-all registered before the /{notification}/read
        // wildcard for the same reason as /orders/track vs /orders/{order}
        // elsewhere in this file — the literal path must win.
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

        Route::get('/products/{slug}/reviews/mine', [ReviewController::class, 'mine']);
        Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);
        Route::patch('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::post('/reviews/{review}/images', [ReviewImageController::class, 'store']);
        Route::delete('/reviews/{review}/images/{image}', [ReviewImageController::class, 'destroy']);
        Route::post('/reviews/{review}/report', [ReviewReportController::class, 'store']);
    });

    // Public catalog reads (PRD §13) — no auth required.
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/featured-products', [ProductController::class, 'featured']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);
    Route::get('/search', [SearchController::class, 'index']);

    // SEO: resolves a renamed product/category slug to its current one, so
    // the storefront can 301 an old URL instead of 404ing it. See
    // RedirectController's docblock.
    Route::get('/redirects/resolve', [RedirectController::class, 'resolve']);

    // Footer CMS (public, unauthenticated) — one aggregated request for the
    // whole footer + WhatsApp button, see FooterController's docblock.
    Route::get('/footer', FooterController::class);

    // Homepage hero carousel (public, unauthenticated) — see
    // HeroSlideController's docblock.
    Route::get('/hero-slides', HeroSlideController::class);

    // Same throttle tier as the other public unauthenticated write endpoints
    // (register/otp) — a plain email form is an obvious spam target.
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->middleware('throttle:20,1');

    // Cart: intentionally no auth:sanctum middleware — guests (identified by
    // the X-Guest-Session header) and logged-in users share these same
    // routes. CartService resolves whichever applies per-request. Throttled
    // per-IP like the other guest-writable endpoints above — a fresh
    // X-Guest-Session is free to mint, so without this an attacker can both
    // brute-force /cart/coupon codes and spam /cart/items to reserve (and
    // starve) real stock via CartService's 30-minute stock reservation.
    Route::middleware('throttle:60,1')->group(function (): void {
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'store']);
        Route::patch('/cart/items/{item}', [CartController::class, 'update']);
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy']);
        Route::post('/cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('/cart/coupon', [CartController::class, 'removeCoupon']);

        // Wishlist: same guest/auth duality as Cart — no auth:sanctum
        // requirement, WishlistService::resolveIdentity handles both.
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist', [WishlistController::class, 'clear']);
        Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);
    });

    // Admin (PRD §19/§19.1) — every route requires a valid access token AND
    // the specific permission its module needs; RBAC (roles/permissions) was
    // built and seeded in Phase 2, this is its first real use. Higher ceiling
    // than the general-authenticated group above since a busy admin UI
    // (list pages, filters, dashboard polling) legitimately fires more
    // requests per staff member than a shopper's session does.
    Route::prefix('admin')->middleware(['auth:sanctum', 'abilities:access-api', 'throttle:240,1'])->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->middleware('staff');
        Route::get('/dashboard/analytics', [AdminDashboardController::class, 'analytics'])->middleware('staff');

        Route::middleware('permission:products.view')->group(function (): void {
            Route::get('/products', [AdminProductController::class, 'index']);
            // Registered before /products/{product} so the literal path
            // wins over the wildcard (same rule as /orders/track elsewhere
            // in this file).
            Route::get('/products/export', [AdminProductController::class, 'export']);
            Route::get('/products/{product}', [AdminProductController::class, 'show']);
        });
        Route::middleware('permission:products.edit')->group(function (): void {
            Route::post('/products', [AdminProductController::class, 'store']);
            Route::patch('/products/{product}', [AdminProductController::class, 'update']);
            Route::post('/products/{product}/variants', [AdminProductVariantController::class, 'store']);
            Route::patch('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'update']);
            Route::post('/products/{product}/images', [AdminProductImageController::class, 'store']);
            // /images/reorder must be registered before /images/{image} so the
            // literal path wins over the wildcard (same rule as /orders/track
            // vs /orders/{orderNumber} elsewhere in this file).
            Route::patch('/products/{product}/images/reorder', [AdminProductImageController::class, 'reorder']);
            Route::patch('/products/{product}/images/{image}', [AdminProductImageController::class, 'update']);
            Route::delete('/products/{product}/images/{image}', [AdminProductImageController::class, 'destroy']);
        });
        Route::middleware('permission:products.delete')->group(function (): void {
            Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);
            Route::delete('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'destroy']);
        });

        Route::middleware('permission:categories.view')->group(function (): void {
            Route::get('/categories', [AdminCategoryController::class, 'index']);
        });
        Route::middleware('permission:categories.edit')->group(function (): void {
            Route::post('/categories', [AdminCategoryController::class, 'store']);
            Route::patch('/categories/{category}', [AdminCategoryController::class, 'update']);
            Route::post('/categories/{category}/image', [AdminCategoryController::class, 'uploadImage']);
            Route::delete('/categories/{category}/image', [AdminCategoryController::class, 'deleteImage']);
        });
        Route::middleware('permission:categories.delete')->group(function (): void {
            Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
        });

        Route::middleware('permission:customers.view')->group(function (): void {
            Route::get('/customers', [AdminCustomerController::class, 'index']);
            Route::get('/customers/export', [AdminCustomerController::class, 'export']);
            Route::get('/customers/{customer}', [AdminCustomerController::class, 'show']);
        });
        Route::middleware('permission:customers.edit')->group(function (): void {
            Route::patch('/customers/{customer}', [AdminCustomerController::class, 'update']);
        });

        Route::middleware('permission:inventory.adjust')->group(function (): void {
            Route::post('/inventory/{variant_id}/adjust', [AdminInventoryController::class, 'adjust']);
        });

        Route::middleware('permission:orders.view')->group(function (): void {
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/export', [AdminOrderController::class, 'export']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        });
        Route::middleware('permission:orders.edit')->group(function (): void {
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        });

        Route::middleware('permission:delivery.view')->group(function (): void {
            Route::get('/delivery-fees', [AdminDeliveryFeeController::class, 'index']);
        });
        Route::middleware('permission:delivery.edit')->group(function (): void {
            Route::patch('/delivery-fees/{wilaya_code}', [AdminDeliveryFeeController::class, 'update']);
            Route::post('/orders/{order}/shipments', [AdminShipmentController::class, 'store']);
            Route::patch('/shipments/{shipment}/status', [AdminShipmentController::class, 'updateStatus']);
        });

        Route::middleware('permission:reviews.view')->group(function (): void {
            Route::get('/reviews', [AdminReviewController::class, 'index']);
        });
        Route::middleware('permission:reviews.moderate')->group(function (): void {
            Route::patch('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus']);
        });

        Route::middleware('permission:coupons.view')->group(function (): void {
            Route::get('/coupons', [AdminCouponController::class, 'index']);
            Route::get('/coupons/{coupon}', [AdminCouponController::class, 'show']);
        });
        Route::middleware('permission:coupons.edit')->group(function (): void {
            Route::post('/coupons', [AdminCouponController::class, 'store']);
            Route::patch('/coupons/{coupon}', [AdminCouponController::class, 'update']);
        });
        Route::middleware('permission:coupons.delete')->group(function (): void {
            Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy']);
        });

        Route::middleware('permission:payments.view')->group(function (): void {
            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::get('/payments/{payment}', [AdminPaymentController::class, 'show']);
        });
        Route::middleware('permission:payments.reconcile')->group(function (): void {
            Route::patch('/payments/{payment}/reconcile', [AdminPaymentController::class, 'reconcile']);
        });

        // Roles & Permissions (PRD §19.1/§25 Phase 10) — roles.view/roles.edit
        // are the two permission keys RolePermissionSeeder deliberately never
        // grants to "Admin", only "Super Admin". Staff account management
        // (who holds which role) is gated by the same two keys — assigning a
        // role to a person is part of role administration, not a distinct
        // capability with its own permission key.
        Route::middleware('permission:roles.view')->group(function (): void {
            Route::get('/roles', [AdminRoleController::class, 'index']);
            Route::get('/roles/{role}', [AdminRoleController::class, 'show']);
            Route::get('/permissions', [AdminPermissionController::class, 'index']);
            Route::get('/staff', [AdminStaffController::class, 'index']);
        });
        Route::middleware('permission:roles.edit')->group(function (): void {
            Route::patch('/roles/{role}', [AdminRoleController::class, 'update']);
            Route::post('/staff', [AdminStaffController::class, 'store']);
            Route::patch('/staff/{staff}', [AdminStaffController::class, 'update']);
        });

        Route::middleware('permission:settings.view')->group(function (): void {
            Route::get('/settings', [AdminSettingsController::class, 'show']);
        });
        Route::middleware('permission:settings.edit')->group(function (): void {
            Route::patch('/settings', [AdminSettingsController::class, 'update']);
        });

        Route::middleware('permission:audit_logs.view')->group(function (): void {
            Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
        });

        // Footer CMS admin management (features/columns/links/social/payment
        // methods + a read-only subscriber list) — the About text and
        // WhatsApp config are store_settings fields, already covered above.
        Route::middleware('permission:footer.view')->group(function (): void {
            Route::get('/footer/features', [AdminFooterFeatureController::class, 'index']);
            Route::get('/footer/columns', [AdminFooterColumnController::class, 'index']);
            Route::get('/footer/social-links', [AdminFooterSocialLinkController::class, 'index']);
            Route::get('/footer/payment-methods', [AdminFooterPaymentMethodController::class, 'index']);
            Route::get('/newsletter-subscribers', [AdminNewsletterSubscriberController::class, 'index']);
        });
        Route::middleware('permission:footer.edit')->group(function (): void {
            Route::post('/footer/features', [AdminFooterFeatureController::class, 'store']);
            Route::patch('/footer/features/{footerFeature}', [AdminFooterFeatureController::class, 'update']);
            Route::delete('/footer/features/{footerFeature}', [AdminFooterFeatureController::class, 'destroy']);

            Route::post('/footer/columns', [AdminFooterColumnController::class, 'store']);
            Route::patch('/footer/columns/{footerColumn}', [AdminFooterColumnController::class, 'update']);
            Route::delete('/footer/columns/{footerColumn}', [AdminFooterColumnController::class, 'destroy']);

            Route::post('/footer/columns/{footerColumn}/links', [AdminFooterLinkController::class, 'store']);
            Route::patch('/footer/links/{footerLink}', [AdminFooterLinkController::class, 'update']);
            Route::delete('/footer/links/{footerLink}', [AdminFooterLinkController::class, 'destroy']);

            Route::post('/footer/social-links', [AdminFooterSocialLinkController::class, 'store']);
            Route::patch('/footer/social-links/{footerSocialLink}', [AdminFooterSocialLinkController::class, 'update']);
            Route::delete('/footer/social-links/{footerSocialLink}', [AdminFooterSocialLinkController::class, 'destroy']);

            Route::post('/footer/payment-methods', [AdminFooterPaymentMethodController::class, 'store']);
            Route::patch('/footer/payment-methods/{footerPaymentMethod}', [AdminFooterPaymentMethodController::class, 'update']);
            Route::delete('/footer/payment-methods/{footerPaymentMethod}', [AdminFooterPaymentMethodController::class, 'destroy']);
        });

        // Homepage hero carousel admin management.
        Route::middleware('permission:hero_slides.view')->group(function (): void {
            Route::get('/hero-slides', [AdminHeroSlideController::class, 'index']);
        });
        Route::middleware('permission:hero_slides.edit')->group(function (): void {
            Route::post('/hero-slides', [AdminHeroSlideController::class, 'store']);
            // /reorder is registered before /{heroSlide} so the literal path
            // wins over the wildcard (same rule as /orders/track elsewhere
            // in this file).
            Route::patch('/hero-slides/reorder', [AdminHeroSlideController::class, 'reorder']);
            Route::patch('/hero-slides/{heroSlide}', [AdminHeroSlideController::class, 'update']);
            Route::delete('/hero-slides/{heroSlide}', [AdminHeroSlideController::class, 'destroy']);
            Route::post('/hero-slides/{heroSlide}/image', [AdminHeroSlideController::class, 'uploadImage']);
            Route::post('/hero-slides/{heroSlide}/mobile-image', [AdminHeroSlideController::class, 'uploadMobileImage']);
        });
    });
});

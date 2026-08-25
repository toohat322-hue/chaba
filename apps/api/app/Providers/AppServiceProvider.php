<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\FooterColumn;
use App\Models\FooterFeature;
use App\Models\FooterLink;
use App\Models\FooterPaymentMethod;
use App\Models\FooterSocialLink;
use App\Models\HeroSlide;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Services\Payments\CibPaymentProvider;
use App\Services\Payments\CodPaymentProvider;
use App\Services\Payments\EdahabiaPaymentProvider;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\WhatsappPaymentProvider;
use App\Services\Shipments\CourierResolver;
use App\Services\Shipments\ManualCourierProvider;
use App\Support\CatalogCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Provider-name-keyed resolver (PRD §7.9) — only gateways that are
        // actually enabled/configured are registered, so resolving a
        // disabled one fails fast at the resolver instead of inside a
        // provider that's guaranteed to throw "not configured".
        $this->app->singleton(PaymentGatewayResolver::class, function ($app) {
            // 'whatsapp' is registered unconditionally, unlike cib/edahabia —
            // its real availability gate is StoreSetting (whatsapp_orders_enabled
            // / whatsapp_active / whatsapp_number), enforced upstream in
            // CheckoutRequest::availablePaymentMethods(), not a static config flag.
            $gateways = ['cod' => CodPaymentProvider::class, 'whatsapp' => WhatsappPaymentProvider::class];

            if (config('services.cib.enabled')) {
                $gateways['cib'] = CibPaymentProvider::class;
            }

            if (config('services.edahabia.enabled')) {
                $gateways['edahabia'] = EdahabiaPaymentProvider::class;
            }

            return new PaymentGatewayResolver($app, $gateways);
        });

        // Courier-name-keyed resolver (PRD §25 Phase 5) — 'manual' is the
        // only courier today (no real courier API integration exists yet).
        $this->app->singleton(CourierResolver::class, function ($app) {
            return new CourierResolver($app, ['manual' => ManualCourierProvider::class]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Public storefront reads (ProductController/CategoryController/
        // HeroSlideController/FooterController) are cached via CatalogCache
        // — flush on any write to the models that feed them, rather than
        // every admin controller remembering to call CatalogCache::flush()
        // itself.
        $cachedModels = [
            Product::class, ProductVariant::class, ProductImage::class, Category::class, Inventory::class,
            HeroSlide::class, StoreSetting::class, FooterColumn::class, FooterLink::class,
            FooterFeature::class, FooterPaymentMethod::class, FooterSocialLink::class,
        ];
        foreach ($cachedModels as $model) {
            $model::saved(fn () => CatalogCache::flush());
            $model::deleted(fn () => CatalogCache::flush());
        }

        // Google/Facebook are natively supported by Laravel Socialite;
        // Apple's non-standard flow (form_post callback, JWT client_secret
        // generated from a private key) needs this community driver's
        // standard registration hook.
        Event::listen(SocialiteWasCalled::class, [AppleExtendSocialite::class, 'handle']);
    }
}

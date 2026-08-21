<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FooterColumn;
use App\Models\FooterFeature;
use App\Models\FooterPaymentMethod;
use App\Models\FooterSocialLink;
use App\Models\StoreSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * One aggregated, public, unauthenticated response for the whole storefront
 * footer + the WhatsApp floating button (see app/[locale]/(storefront)/layout.tsx,
 * which fetches this once and passes it down as props) — everything the
 * footer needs in a single request rather than one per section.
 */
class FooterController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = StoreSetting::current();

        $features = FooterFeature::where('is_active', true)->orderBy('sort_order')->get();
        $socialLinks = FooterSocialLink::where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = FooterPaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $columns = FooterColumn::where('is_active', true)
            ->with(['links' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success([
            'settings' => [
                'about' => [
                    'title' => [
                        'ar' => $settings->about_title_ar,
                        'fr' => $settings->about_title_fr,
                        'en' => $settings->about_title_en,
                    ],
                    'description' => [
                        'ar' => $settings->about_description_ar,
                        'fr' => $settings->about_description_fr,
                        'en' => $settings->about_description_en,
                    ],
                ],
                'whatsapp' => [
                    // Visible if either the contact FAB or order-checkout
                    // path needs it — the checkout page reads this same
                    // /footer payload rather than a second endpoint.
                    'number' => ($settings->whatsapp_active || $settings->whatsapp_orders_enabled) ? $settings->whatsapp_number : null,
                    'orders_enabled' => $settings->whatsapp_orders_enabled,
                    'message' => [
                        'ar' => $settings->whatsapp_message_ar,
                        'fr' => $settings->whatsapp_message_fr,
                        'en' => $settings->whatsapp_message_en,
                    ],
                ],
            ],
            'features' => $features->map(fn ($feature) => [
                'id' => $feature->id,
                'icon' => $feature->icon,
                'title' => ['ar' => $feature->title_ar, 'fr' => $feature->title_fr, 'en' => $feature->title_en],
                'description' => [
                    'ar' => $feature->description_ar,
                    'fr' => $feature->description_fr,
                    'en' => $feature->description_en,
                ],
            ]),
            'socialLinks' => $socialLinks->map(fn ($link) => [
                'platform' => $link->platform,
                'url' => $link->url,
            ]),
            'columns' => $columns->map(fn ($column) => [
                'id' => $column->id,
                'title' => ['ar' => $column->title_ar, 'fr' => $column->title_fr, 'en' => $column->title_en],
                'links' => $column->links->map(fn ($link) => [
                    'label' => ['ar' => $link->label_ar, 'fr' => $link->label_fr, 'en' => $link->label_en],
                    'url' => $link->url,
                ]),
            ]),
            'paymentMethods' => $paymentMethods->map(fn ($method) => [
                'name' => ['ar' => $method->name_ar, 'fr' => $method->name_fr, 'en' => $method->name_en],
                'icon' => $method->icon,
            ]),
        ]);
    }
}

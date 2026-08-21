<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Models\StoreSetting;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::success($this->present(StoreSetting::current()));
    }

    public function update(UpdateStoreSettingsRequest $request): JsonResponse
    {
        $settings = StoreSetting::current();
        $settings->fill($request->validated());
        $changes = AuditLogger::diff($settings);
        $settings->save();

        AuditLogger::log($request->user(), 'settings.updated', $settings, $changes);

        return ApiResponse::success($this->present($settings));
    }

    private function present(StoreSetting $settings): array
    {
        return [
            'store_name' => $settings->store_name,
            'support_email' => $settings->support_email,
            'support_phone' => $settings->support_phone,
            'store_address' => $settings->store_address,
            'tax_rate_bps' => $settings->tax_rate_bps,
            // Read-only: real activation is env-driven (merchant secrets
            // aren't stored in this table) — surfaced so an admin can see
            // current status without shell access.
            'payment_providers' => [
                'cod' => true,
                'cib' => (bool) config('services.cib.enabled'),
                'edahabia' => (bool) config('services.edahabia.enabled'),
            ],
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
                'number' => $settings->whatsapp_number,
                'active' => $settings->whatsapp_active,
                'orders_enabled' => $settings->whatsapp_orders_enabled,
                'message' => [
                    'ar' => $settings->whatsapp_message_ar,
                    'fr' => $settings->whatsapp_message_fr,
                    'en' => $settings->whatsapp_message_en,
                ],
            ],
        ];
    }
}

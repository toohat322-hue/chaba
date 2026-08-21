<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryFee;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeliveryFeeController extends Controller
{
    /**
     * Only `home` is ever populated — pickup/stop-desk isn't offered yet
     * (no courier partner location data exists).
     */
    public function show(string $wilayaCode): JsonResponse
    {
        $home = DeliveryFee::where('wilaya_code', $wilayaCode)->where('delivery_method', 'home')->first();

        return ApiResponse::success([
            'wilaya_code' => $wilayaCode,
            'home' => $home ? [
                'fee' => $home->fee,
                'eta_min_days' => $home->eta_min_days,
                'eta_max_days' => $home->eta_max_days,
            ] : null,
            'pickup' => null,
        ]);
    }
}

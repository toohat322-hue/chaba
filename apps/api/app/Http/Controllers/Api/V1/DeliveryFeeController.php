<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryFee;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeliveryFeeController extends Controller
{
    public function show(string $wilayaCode): JsonResponse
    {
        $fees = DeliveryFee::where('wilaya_code', $wilayaCode)
            ->whereIn('delivery_method', ['home', 'pickup'])
            ->get()
            ->keyBy('delivery_method');

        $present = fn (?DeliveryFee $fee) => $fee ? [
            'fee' => $fee->fee,
            'eta_min_days' => $fee->eta_min_days,
            'eta_max_days' => $fee->eta_max_days,
        ] : null;

        return ApiResponse::success([
            'wilaya_code' => $wilayaCode,
            'home' => $present($fees->get('home')),
            'pickup' => $present($fees->get('pickup')),
        ]);
    }
}

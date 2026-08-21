<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDeliveryFeeRequest;
use App\Models\DeliveryFee;
use App\Models\Wilaya;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;

class DeliveryFeeController extends Controller
{
    /**
     * One row per wilaya (home only — pickup isn't offered yet), including
     * wilayas that have no delivery_fees row for some reason, so the admin
     * table is always exactly the 58 wilayas, never a partial list.
     */
    public function index(): JsonResponse
    {
        $fees = DeliveryFee::where('delivery_method', 'home')->get()->keyBy('wilaya_code');

        $wilayas = Wilaya::query()->orderBy('code')->get();

        return ApiResponse::success($wilayas->map(function (Wilaya $wilaya) use ($fees) {
            $fee = $fees->get($wilaya->code);

            return [
                'wilaya_code' => $wilaya->code,
                'wilaya_name' => ['ar' => $wilaya->name_ar, 'fr' => $wilaya->name_fr, 'en' => $wilaya->name_en],
                'fee' => $fee->fee ?? 0,
                'eta_min_days' => $fee->eta_min_days ?? null,
                'eta_max_days' => $fee->eta_max_days ?? null,
            ];
        }));
    }

    public function update(UpdateDeliveryFeeRequest $request, string $wilayaCode): JsonResponse
    {
        if (! Wilaya::where('code', $wilayaCode)->exists()) {
            throw new ApiException('not_found', 'Wilaya not found.', 404);
        }

        // updateOrCreate() gives no pre-save hook to diff against, so the
        // prior values (if any) are captured first — after the call,
        // getOriginal() would already reflect the new row, same trap
        // AuditLogger::diff() avoids by requiring a pre-save call.
        $before = DeliveryFee::where('wilaya_code', $wilayaCode)->where('delivery_method', 'home')->first();

        $fee = DeliveryFee::updateOrCreate(
            ['wilaya_code' => $wilayaCode, 'delivery_method' => 'home'],
            $request->validated(),
        );

        $changes = $before === null ? null : collect($request->validated())
            ->mapWithKeys(fn ($newValue, $key) => [$key => [$before->getAttribute($key), $newValue]])
            ->filter(fn ($pair) => $pair[0] !== $pair[1])
            ->all();

        AuditLogger::log(
            $request->user(),
            $before === null ? 'delivery_fee.created' : 'delivery_fee.updated',
            $fee,
            $changes === [] ? null : $changes,
        );

        return ApiResponse::success([
            'wilaya_code' => $fee->wilaya_code,
            'fee' => $fee->fee,
            'eta_min_days' => $fee->eta_min_days,
            'eta_max_days' => $fee->eta_max_days,
        ]);
    }
}

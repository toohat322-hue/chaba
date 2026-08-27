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
     * One row per wilaya, including wilayas that have no delivery_fees row
     * for some reason, so the admin table is always exactly the 58 wilayas,
     * never a partial list. home and pickup are separate delivery_fees rows
     * under the hood, presented together here since the admin edits both
     * for a wilaya in one place.
     */
    public function index(): JsonResponse
    {
        $fees = DeliveryFee::whereIn('delivery_method', ['home', 'pickup'])->get()->groupBy('wilaya_code');

        $wilayas = Wilaya::query()->orderBy('code')->get();

        return ApiResponse::success($wilayas->map(function (Wilaya $wilaya) use ($fees) {
            $rows = $fees->get($wilaya->code, collect())->keyBy('delivery_method');
            $home = $rows->get('home');
            $pickup = $rows->get('pickup');

            return [
                'wilaya_code' => $wilaya->code,
                'wilaya_name' => ['ar' => $wilaya->name_ar, 'fr' => $wilaya->name_fr, 'en' => $wilaya->name_en],
                'fee' => $home->fee ?? 0,
                'eta_min_days' => $home->eta_min_days ?? null,
                'eta_max_days' => $home->eta_max_days ?? null,
                'pickup_fee' => $pickup->fee ?? 0,
            ];
        }));
    }

    public function update(UpdateDeliveryFeeRequest $request, string $wilayaCode): JsonResponse
    {
        if (! Wilaya::where('code', $wilayaCode)->exists()) {
            throw new ApiException('not_found', 'Wilaya not found.', 404);
        }

        $validated = $request->validated();

        // updateOrCreate() gives no pre-save hook to diff against, so the
        // prior values (if any) are captured first — after the call,
        // getOriginal() would already reflect the new row, same trap
        // AuditLogger::diff() avoids by requiring a pre-save call.
        $beforeHome = DeliveryFee::where('wilaya_code', $wilayaCode)->where('delivery_method', 'home')->first();
        $beforePickup = DeliveryFee::where('wilaya_code', $wilayaCode)->where('delivery_method', 'pickup')->first();

        $home = DeliveryFee::updateOrCreate(
            ['wilaya_code' => $wilayaCode, 'delivery_method' => 'home'],
            [
                'fee' => $validated['fee'],
                'eta_min_days' => $validated['eta_min_days'] ?? null,
                'eta_max_days' => $validated['eta_max_days'] ?? null,
            ],
        );

        $pickup = DeliveryFee::updateOrCreate(
            ['wilaya_code' => $wilayaCode, 'delivery_method' => 'pickup'],
            ['fee' => $validated['pickup_fee']],
        );

        $changes = array_filter([
            'fee' => $beforeHome && $beforeHome->fee === $home->fee ? null : [$beforeHome?->fee, $home->fee],
            'pickup_fee' => $beforePickup && $beforePickup->fee === $pickup->fee ? null : [$beforePickup?->fee, $pickup->fee],
        ]);

        AuditLogger::log(
            $request->user(),
            $beforeHome === null ? 'delivery_fee.created' : 'delivery_fee.updated',
            $home,
            $changes === [] ? null : $changes,
        );

        return ApiResponse::success([
            'wilaya_code' => $home->wilaya_code,
            'fee' => $home->fee,
            'eta_min_days' => $home->eta_min_days,
            'eta_max_days' => $home->eta_max_days,
            'pickup_fee' => $pickup->fee,
        ]);
    }
}

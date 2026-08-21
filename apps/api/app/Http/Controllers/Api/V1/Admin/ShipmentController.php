<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentStatusRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShipmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// PRD §25 Phase 5/§19: shipment creation + tracking, gated by the same
// delivery.view/delivery.edit permissions already used for delivery-fee
// management (PermissionSeeder describes them as "View shipments"/"Manage
// courier config/shipments" — this is their first real use for shipments).
class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function store(StoreShipmentRequest $request, string $order): JsonResponse
    {
        $model = Order::find($order);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Order not found.');
        }

        $shipment = $this->shipments->createShipment($model, $request->validated());

        return ApiResponse::success(new ShipmentResource($shipment), 201);
    }

    public function updateStatus(UpdateShipmentStatusRequest $request, string $shipment): JsonResponse
    {
        $model = Shipment::find($shipment);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Shipment not found.');
        }

        $updated = $this->shipments->updateStatus($model, $request->string('status')->toString(), $request->validated());

        return ApiResponse::success(new ShipmentResource($updated));
    }
}

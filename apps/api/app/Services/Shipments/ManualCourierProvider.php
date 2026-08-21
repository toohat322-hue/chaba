<?php

namespace App\Services\Shipments;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Shipment;

/**
 * No real courier API integration exists yet (no partner chosen, no
 * credentials issued) — this models shipment tracking as admin-entered data
 * (courier name, tracking number, status typed in by staff) instead of a
 * live courier API, which also happens to match how COD-heavy Algerian
 * e-commerce commonly operates before a courier integration exists: staff
 * coordinate with the courier by phone and record tracking info manually.
 * Swapping in a real courier later means implementing CourierInterface
 * again and registering it in CourierResolver — no changes to
 * ShipmentService or the admin routes/UI that call it.
 */
class ManualCourierProvider implements CourierInterface
{
    public function createShipment(Order $order): Shipment
    {
        return $order->shipments()->create([
            'status' => 'pending',
        ]);
    }

    public function cancelShipment(Shipment $shipment): Shipment
    {
        $shipment->update(['status' => 'failed', 'failure_reason' => 'Cancelled by admin.']);

        return $shipment->fresh();
    }

    public function trackShipment(string $trackingNumber): Shipment
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)->first();

        if (! $shipment) {
            throw ApiException::notFound('not_found', 'No shipment found for this tracking number.');
        }

        return $shipment;
    }

    public function getLabel(Shipment $shipment): ?string
    {
        // No real courier to generate a shipping label from.
        return null;
    }
}

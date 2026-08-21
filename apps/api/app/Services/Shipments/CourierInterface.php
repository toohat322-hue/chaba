<?php

namespace App\Services\Shipments;

use App\Models\Order;
use App\Models\Shipment;

/**
 * PRD §25 Phase 5: courier-agnostic shipping abstraction — a new courier
 * partner plugs in by implementing this interface and registering it in
 * CourierResolver, without touching ShipmentService/order logic. No real
 * courier API integration exists yet (no partner chosen, no credentials
 * issued) — ManualCourierProvider is the only implementation today.
 */
interface CourierInterface
{
    public function createShipment(Order $order): Shipment;

    public function cancelShipment(Shipment $shipment): Shipment;

    public function trackShipment(string $trackingNumber): Shipment;

    /**
     * @return string|null A URL/reference to a printable label, or null if
     *                     this courier doesn't support label generation.
     */
    public function getLabel(Shipment $shipment): ?string;
}

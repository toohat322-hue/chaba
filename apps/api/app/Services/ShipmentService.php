<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipments\CourierResolver;

/**
 * PRD §25 Phase 5/§7.8: shipment creation + tracking lifecycle, mirroring
 * OrderService's forward-only status-transition discipline. Deliberately
 * decoupled from Order::order_status — a shipment is admin-managed logistics
 * detail (which courier, what tracking number, what the courier last
 * reported) layered on top of the order lifecycle, not a re-derivation of it.
 */
class ShipmentService
{
    private const FORWARD_TRANSITIONS = [
        'pending' => ['picked_up', 'failed'],
        'picked_up' => ['in_transit', 'failed'],
        'in_transit' => ['out_for_delivery', 'failed'],
        'out_for_delivery' => ['delivered', 'failed'],
        'failed' => ['in_transit', 'returned'],
    ];

    public function __construct(private readonly CourierResolver $couriers) {}

    /**
     * $data['courier_partner'] is a free-text label for staff record-keeping
     * (e.g. "Yalidine", "ZR Express") — distinct from the CourierInterface
     * implementation used to create the shipment, which always resolves to
     * 'manual' (the only one that exists) regardless of which real-world
     * courier company is handling the delivery.
     */
    public function createShipment(Order $order, array $data = []): Shipment
    {
        $shipment = $this->couriers->resolve(null)->createShipment($order);

        $shipment->update(array_filter([
            'courier_partner' => $data['courier_partner'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
        ], fn ($value) => $value !== null));

        return $shipment->fresh();
    }

    public function updateStatus(Shipment $shipment, string $newStatus, array $data = []): Shipment
    {
        $allowed = self::FORWARD_TRANSITIONS[$shipment->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ApiException::businessRule(
                'invalid_shipment_status_transition',
                "Cannot move a shipment from {$shipment->status} to {$newStatus}.",
            );
        }

        $shipment->status = $newStatus;

        if (isset($data['tracking_number'])) {
            $shipment->tracking_number = $data['tracking_number'];
        }

        if (isset($data['courier_partner'])) {
            $shipment->courier_partner = $data['courier_partner'];
        }

        if (isset($data['estimated_delivery_date'])) {
            $shipment->estimated_delivery_date = $data['estimated_delivery_date'];
        }

        if ($newStatus === 'delivered') {
            $shipment->delivered_at = now();
        }

        if ($newStatus === 'failed' && isset($data['failure_reason'])) {
            $shipment->failure_reason = $data['failure_reason'];
        }

        $shipment->save();

        return $shipment->fresh();
    }
}

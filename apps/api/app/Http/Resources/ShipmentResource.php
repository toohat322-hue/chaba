<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shipment */
class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'courier_partner' => $this->courier_partner,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'estimated_delivery_date' => $this->estimated_delivery_date,
            'delivered_at' => $this->delivered_at,
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

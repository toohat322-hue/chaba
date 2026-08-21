<?php

namespace App\Http\Resources\Admin;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class AdminPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order->order_number),
            'provider' => $this->provider,
            'transaction_ref' => $this->transaction_ref,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'raw_response' => $this->raw_response,
            'reconciliation_status' => $this->reconciliation_status,
            'reconciled_at' => $this->reconciled_at,
            'reconciled_by' => $this->whenLoaded('reconciler', fn () => $this->reconciler?->full_name),
            'reconciliation_notes' => $this->reconciliation_notes,
            'refunds' => $this->whenLoaded('refunds', fn () => $this->refunds->map(fn ($refund) => [
                'id' => $refund->id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status,
                'created_at' => $refund->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

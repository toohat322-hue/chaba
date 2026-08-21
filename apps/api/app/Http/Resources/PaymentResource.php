<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'transaction_ref' => $this->transaction_ref,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            // raw_response deliberately excluded — gateway-internal payload,
            // not meant for the customer-facing order response.
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

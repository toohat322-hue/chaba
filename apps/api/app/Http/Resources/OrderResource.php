<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'whatsapp_status' => $this->whatsapp_status,
            'delivery_method' => $this->delivery_method,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'delivery_fee' => $this->delivery_fee,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'coupon' => $this->whenLoaded('coupon', fn () => $this->coupon ? [
                'code' => $this->coupon->code,
                'type' => $this->coupon->type,
            ] : null),
            'notes' => $this->notes,
            'customer' => [
                'name' => $this->guest_name,
                'phone' => $this->guest_phone,
                'email' => $this->guest_email,
            ],
            'address' => $this->whenLoaded('address', fn () => [
                'full_name' => $this->address->full_name,
                'phone' => $this->address->phone,
                'wilaya' => [
                    'code' => $this->address->wilaya_code,
                    'name' => $this->address->relationLoaded('wilaya') ? [
                        'ar' => $this->address->wilaya->name_ar,
                        'fr' => $this->address->wilaya->name_fr,
                        'en' => $this->address->wilaya->name_en,
                    ] : null,
                ],
                'commune' => $this->address->relationLoaded('commune') ? [
                    'ar' => $this->address->commune->name_ar,
                    'fr' => $this->address->commune->name_fr,
                    'en' => $this->address->commune->name_en,
                ] : null,
                'address_line' => $this->address->address_line,
                'landmark' => $this->address->landmark,
                'postal_code' => $this->address->postal_code,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'variant_id' => $item->variant_id,
                'product_name' => $item->product_name_snapshot,
                'sku' => $item->sku_snapshot,
                'size' => $item->size_snapshot,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])),
            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($entry) => [
                'status' => $entry->status,
                'note' => $entry->note,
                'created_at' => $entry->created_at,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
            'shipments' => $this->whenLoaded('shipments', fn () => ShipmentResource::collection($this->shipments)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

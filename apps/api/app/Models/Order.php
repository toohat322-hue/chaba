<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_number', 'user_id', 'guest_name', 'guest_phone', 'guest_email',
        'address_id', 'delivery_method', 'payment_method', 'payment_status', 'order_status',
        'subtotal', 'discount_total', 'delivery_fee', 'tax_total', 'grand_total',
        'coupon_id', 'notes', 'whatsapp_status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'delivery_fee' => 'integer',
            'tax_total' => 'integer',
            'grand_total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}

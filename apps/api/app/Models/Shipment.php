<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'courier_partner', 'tracking_number', 'status',
        'estimated_delivery_date', 'delivered_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'estimated_delivery_date' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

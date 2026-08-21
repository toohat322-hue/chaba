<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    use HasUuids;

    // Immutable receipt log entry (status is updated once after processing,
    // but there's no meaningful "updated_at" beyond that) — matches the
    // created_at-only convention used by Refund.
    public $timestamps = false;

    protected $fillable = ['gateway', 'payload_hash', 'transaction_ref', 'payload', 'status', 'error_message'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

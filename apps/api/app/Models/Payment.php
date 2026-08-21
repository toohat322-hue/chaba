<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'provider', 'transaction_ref', 'status', 'amount', 'currency', 'raw_response',
        'reconciliation_status', 'reconciled_at', 'reconciled_by', 'reconciliation_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'raw_response' => 'array',
            'reconciled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}

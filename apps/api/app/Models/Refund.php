<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasUuids;

    // Migration only defines created_at (no updated_at) — a refund is an
    // immutable ledger entry, never edited after the fact.
    public $timestamps = false;

    protected $fillable = ['payment_id', 'amount', 'reason', 'status', 'actor_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

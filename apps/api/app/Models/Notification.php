<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    // Migration only defines created_at (no updated_at) — matches Payment/
    // Shipment/etc.; status/read_at mutations don't need a change-tracking column.
    public $timestamps = false;

    protected $fillable = ['user_id', 'channel', 'event_type', 'payload', 'status', 'sent_at', 'read_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

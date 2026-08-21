<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasUuids;

    // PRD §12 names this table `order_status_history` (singular) — override
    // Eloquent's default `order_status_histories` auto-pluralization guess.
    protected $table = 'order_status_history';

    public $timestamps = false;

    protected $fillable = ['order_id', 'status', 'note', 'actor_id', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

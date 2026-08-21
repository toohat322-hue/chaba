<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryFee extends Model
{
    use HasUuids;

    protected $fillable = ['wilaya_code', 'delivery_method', 'fee', 'eta_min_days', 'eta_max_days'];

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'eta_min_days' => 'integer',
            'eta_max_days' => 'integer',
        ];
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class, 'wilaya_code', 'code');
    }
}

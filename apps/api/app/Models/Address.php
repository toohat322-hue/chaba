<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'full_name', 'phone', 'wilaya_code', 'commune_id',
        'address_line', 'landmark', 'postal_code', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class, 'wilaya_code', 'code');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }
}

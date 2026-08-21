<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilaya extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['code', 'name_ar', 'name_fr', 'name_en'];

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class, 'wilaya_code', 'code');
    }

    public function deliveryFees(): HasMany
    {
        return $this->hasMany(DeliveryFee::class, 'wilaya_code', 'code');
    }
}

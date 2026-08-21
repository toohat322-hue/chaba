<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['wilaya_code', 'name_ar', 'name_fr', 'name_en'];

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class, 'wilaya_code', 'code');
    }
}

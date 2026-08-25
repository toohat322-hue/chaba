<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['review_id', 'url'];

    protected function url(): Attribute
    {
        return Attribute::make(get: fn (?string $value) => MediaUrl::proxy($value));
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}

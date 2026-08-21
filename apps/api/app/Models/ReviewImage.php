<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['review_id', 'url'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}

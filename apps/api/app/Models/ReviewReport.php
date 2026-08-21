<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['review_id', 'reporter_user_id', 'reason', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }
}

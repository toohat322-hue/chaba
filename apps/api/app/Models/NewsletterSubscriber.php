<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasUuids;

    protected $fillable = ['email', 'locale', 'subscribed_at', 'unsubscribe_token'];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
        ];
    }
}

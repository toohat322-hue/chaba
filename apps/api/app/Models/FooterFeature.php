<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FooterFeature extends Model
{
    use HasUuids;

    protected $fillable = [
        'icon', 'title_ar', 'title_fr', 'title_en',
        'description_ar', 'description_fr', 'description_en',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

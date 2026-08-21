<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterColumn extends Model
{
    use HasUuids;

    protected $fillable = ['title_ar', 'title_fr', 'title_en', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function links(): HasMany
    {
        return $this->hasMany(FooterLink::class)->orderBy('sort_order');
    }
}

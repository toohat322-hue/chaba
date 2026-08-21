<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FooterLink extends Model
{
    use HasUuids;

    protected $fillable = ['footer_column_id', 'label_ar', 'label_fr', 'label_en', 'url', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(FooterColumn::class, 'footer_column_id');
    }
}

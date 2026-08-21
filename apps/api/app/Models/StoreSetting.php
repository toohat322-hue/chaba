<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_name', 'support_email', 'support_phone', 'store_address', 'tax_rate_bps',
        'about_title_ar', 'about_title_fr', 'about_title_en',
        'about_description_ar', 'about_description_fr', 'about_description_en',
        'whatsapp_number', 'whatsapp_message_ar', 'whatsapp_message_fr', 'whatsapp_message_en',
        'whatsapp_active', 'whatsapp_orders_enabled',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate_bps' => 'integer',
            'whatsapp_active' => 'boolean',
            'whatsapp_orders_enabled' => 'boolean',
        ];
    }

    /**
     * Singleton accessor — the table is always exactly one row. Created
     * on first access with sane defaults rather than requiring a seeder.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'store_name' => config('app.name', 'CHABA'),
            'tax_rate_bps' => 0,
        ]);
    }
}

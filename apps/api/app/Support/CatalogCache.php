<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Thin wrapper around a tagged cache region for public catalog reads
 * (categories, products). Reads go through remember(); any write to a
 * Product/ProductVariant/ProductImage/Category/Inventory model flushes the
 * whole region (see AppServiceProvider::boot()) rather than tracking
 * per-key invalidation — catalog writes are rare (admin-only) compared to
 * reads, so a coarse flush-on-write is simpler and cheap.
 */
class CatalogCache
{
    private const TTL_MINUTES = 10;

    private const TAG = 'catalog';

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::tags([self::TAG])->remember($key, now()->addMinutes(self::TTL_MINUTES), $callback);
    }

    public static function flush(): void
    {
        Cache::tags([self::TAG])->flush();
    }
}

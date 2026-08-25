<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Thin wrapper around a tagged cache region for public, read-heavy,
 * admin-written storefront data — catalog (categories, products) plus the
 * hero slider and footer content, which are fetched on nearly every page
 * load but only ever change from the admin panel. Reads go through
 * remember(); any write to one of the models listed in
 * AppServiceProvider::boot() flushes the whole region rather than tracking
 * per-key invalidation — these writes are rare (admin-only) compared to
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

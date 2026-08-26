<?php

namespace App\Support;

class MediaUrl
{
    /**
     * Extract the object key from a stored URL, or null if it isn't a
     * recognizable object on our own bucket (e.g. a hand-entered external
     * URL on a category, which should pass through untouched).
     *
     * Matches against both AWS_URL (R2's public domain, used in production)
     * and AWS_ENDPOINT (local MinIO has no separate public URL, so uploads
     * fall back to this one) — broad on purpose, since this backs deletion
     * and needs to keep working in every environment the app runs in.
     */
    public static function key(?string $url): ?string
    {
        return self::extractKey($url, array_filter([
            self::hostOf(config('filesystems.disks.s3.url')),
            self::hostOf(config('filesystems.disks.s3.endpoint')),
        ]));
    }

    /**
     * Rewrite a stored URL to one served through our own API domain — but
     * only for URLs on the public AWS_URL host specifically.
     *
     * Browsers connecting straight to R2's r2.dev domain have shown genuine
     * TLS handshake failures (confirmed independently against three
     * different TLS stacks — Windows Schannel, OpenSSL, and the network
     * client behind Claude's own WebFetch tool), which Cloudflare's own
     * docs attribute to r2.dev being a shared testing/dev domain, not a
     * production one. Routing through MediaProxyController avoids that
     * entirely, since our own API domain has never shown this problem.
     * Local dev's direct MinIO URLs (AWS_URL unset) are deliberately left
     * untouched — they don't have r2.dev's problem, and rewriting them would
     * just add a pointless hop. Falls back to the original value for
     * anything not on our bucket.
     */
    public static function proxy(?string $url): ?string
    {
        $key = self::extractKey($url, array_filter([self::hostOf(config('filesystems.disks.s3.url'))]));

        if (! $key) {
            return $url;
        }

        return rtrim(config('app.url'), '/').'/media/'.$key;
    }

    /**
     * @param  list<string>  $recognizedHosts
     */
    private static function extractKey(?string $url, array $recognizedHosts): ?string
    {
        if (! $url || $recognizedHosts === []) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || ! in_array($host, $recognizedHosts, true)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $bucket = config('filesystems.disks.s3.bucket');
        $key = $path ? ltrim(str_replace('/'.$bucket, '', $path), '/') : null;

        return $key !== null && $key !== '' ? $key : null;
    }

    private static function hostOf(?string $url): ?string
    {
        return $url ? parse_url($url, PHP_URL_HOST) : null;
    }
}

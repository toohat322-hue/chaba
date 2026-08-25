<?php

namespace App\Support;

class MediaUrl
{
    /**
     * Extract the R2 object key from a stored URL, or null if it isn't a
     * recognizable object on our own bucket (e.g. a hand-entered external
     * URL on a category, which should pass through untouched).
     */
    public static function key(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $s3Url = config('filesystems.disks.s3.url');
        $s3Host = $s3Url ? parse_url($s3Url, PHP_URL_HOST) : null;
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || ! $s3Host || $host !== $s3Host) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $bucket = config('filesystems.disks.s3.bucket');
        $key = $path ? ltrim(str_replace('/'.$bucket, '', $path), '/') : null;

        return $key !== null && $key !== '' ? $key : null;
    }

    /**
     * Rewrite a stored R2 URL to one served through our own API domain.
     *
     * Browsers connecting straight to R2's r2.dev domain have shown genuine
     * TLS handshake failures (confirmed independently against three
     * different TLS stacks — Windows Schannel, OpenSSL, and the network
     * client behind Claude's own WebFetch tool), which Cloudflare's own
     * docs attribute to r2.dev being a shared testing/dev domain, not a
     * production one. Routing through MediaProxyController avoids that
     * entirely, since our own API domain has never shown this problem.
     * Falls back to the original value for anything not on our bucket.
     */
    public static function proxy(?string $url): ?string
    {
        $key = self::key($url);

        if (! $key) {
            return $url;
        }

        return rtrim(config('app.url'), '/').'/media/'.$key;
    }
}

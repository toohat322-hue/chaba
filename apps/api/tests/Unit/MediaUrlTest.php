<?php

namespace Tests\Unit;

use App\Support\MediaUrl;
use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.s3.url' => 'https://pub-test.r2.dev',
            'filesystems.disks.s3.endpoint' => 'http://127.0.0.1:9000',
            'filesystems.disks.s3.bucket' => 'chaba-bucket',
            'app.url' => 'https://chaba-api.onrender.com',
        ]);
    }

    public function test_proxy_rewrites_a_url_on_our_bucket(): void
    {
        $url = 'https://pub-test.r2.dev/chaba-bucket/hero-slides/abc/def.jpg';

        $this->assertSame(
            'https://chaba-api.onrender.com/media/hero-slides/abc/def.jpg',
            MediaUrl::proxy($url),
        );
    }

    public function test_proxy_leaves_an_unrelated_external_url_untouched(): void
    {
        $url = 'https://images.example.com/some-category-photo.jpg';

        $this->assertSame($url, MediaUrl::proxy($url));
    }

    public function test_proxy_passes_through_null(): void
    {
        $this->assertNull(MediaUrl::proxy(null));
    }

    public function test_key_returns_null_for_a_url_not_on_our_host(): void
    {
        $this->assertNull(MediaUrl::key('https://not-our-bucket.example.com/chaba-bucket/foo.jpg'));
    }

    public function test_key_extracts_the_object_key(): void
    {
        $this->assertSame(
            'products/perfume/uuid.jpg',
            MediaUrl::key('https://pub-test.r2.dev/chaba-bucket/products/perfume/uuid.jpg'),
        );
    }

    public function test_key_also_extracts_from_a_local_minio_style_url(): void
    {
        // No AWS_URL configured locally — Storage::disk('s3')->url() falls
        // back to AWS_ENDPOINT with the bucket name in the path (path-style).
        $this->assertSame(
            'products/perfume/uuid.jpg',
            MediaUrl::key('http://127.0.0.1:9000/chaba-bucket/products/perfume/uuid.jpg'),
        );
    }

    public function test_proxy_does_not_rewrite_a_local_minio_style_url(): void
    {
        // Only the public AWS_URL host gets proxied — local MinIO URLs have
        // no r2.dev-style TLS problem, so they're left as-is.
        $url = 'http://127.0.0.1:9000/chaba-bucket/products/perfume/uuid.jpg';

        $this->assertSame($url, MediaUrl::proxy($url));
    }
}

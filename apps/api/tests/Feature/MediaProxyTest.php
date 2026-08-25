<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_it_streams_an_existing_object_with_a_long_cache_header(): void
    {
        Storage::disk('s3')->put('hero-slides/abc/def.jpg', 'fake-image-bytes', 'public');

        $response = $this->get('/media/hero-slides/abc/def.jpg');

        $response->assertStatus(200);
        // Symfony's ResponseHeaderBag normalizes Cache-Control directives
        // alphabetically regardless of the order they're set in.
        $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $this->assertSame('fake-image-bytes', $response->streamedContent());
    }

    public function test_it_404s_for_a_missing_object(): void
    {
        $this->get('/media/does/not/exist.jpg')->assertStatus(404);
    }
}

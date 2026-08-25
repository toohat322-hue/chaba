<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaProxyController extends Controller
{
    /**
     * Streams an object that's already public on R2 through our own domain.
     * See MediaUrl::proxy() for why this exists.
     */
    public function show(string $path): StreamedResponse
    {
        try {
            return Storage::disk('s3')->response($path, null, [
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        } catch (Throwable) {
            abort(404);
        }
    }
}

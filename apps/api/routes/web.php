<?php

use App\Http\Controllers\MediaProxyController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// This app is an API-only backend; the customer/admin UI lives in apps/web (Next.js).
Route::get('/', function () {
    return response()->json(['service' => 'chaba-api', 'status' => 'ok']);
});

// Streams already-public R2 objects through our own domain — see
// MediaUrl::proxy() for why. Cookie/session/CSRF middleware is stripped
// since this is a plain cacheable asset route, not an app request.
Route::get('/media/{path}', [MediaProxyController::class, 'show'])
    ->where('path', '.*')
    ->withoutMiddleware([EncryptCookies::class, StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class]);

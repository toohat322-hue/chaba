<?php

use Illuminate\Support\Facades\Route;

// This app is an API-only backend; the customer/admin UI lives in apps/web (Next.js).
Route::get('/', function () {
    return response()->json(['service' => 'chaba-api', 'status' => 'ok']);
});

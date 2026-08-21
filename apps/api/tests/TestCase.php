<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel's test HTTP client reuses one application/container across
     * every $this->postJson()/getJson() call within a single test method.
     * Sanctum's guard is a RequestGuard, which caches the first user it
     * resolves on $this->user and never re-checks — meaning a *second*
     * simulated request in the same test would silently authenticate as
     * whoever the *first* request did, even with a different (or revoked)
     * Bearer token. Real requests never hit this (each is a fresh process),
     * but tests that simulate a sequence of calls — logout then retry,
     * refresh then replay — need every call to re-resolve auth from scratch.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        Auth::forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}

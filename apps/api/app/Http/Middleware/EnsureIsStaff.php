<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the shared admin dashboard, which isn't tied to one module-specific
 * permission — any of the 8 staff roles (PRD §19.1) should see it. Individual
 * modules (products, customers, ...) are further gated by `permission:`.
 */
class EnsureIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role_id) {
            throw new ApiException('forbidden', 'You are not authorized to perform this action.', 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware `permission:orders.edit` — PRD §15: "RBAC enforced
 * server-side on every admin endpoint, never client-side only."
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            throw new ApiException('forbidden', 'You are not authorized to perform this action.', 403);
        }

        return $next($request);
    }
}

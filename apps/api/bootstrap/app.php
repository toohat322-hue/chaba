<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureIsStaff;
use App\Http\Middleware\EnsurePermission;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This is a pure JSON API (no Laravel-rendered login page — the UI
        // lives in apps/web). Laravel's ApplicationBuilder otherwise
        // registers a default guest-redirect to a named `login` web route
        // that doesn't exist here, which throws RouteNotFoundException
        // (masking the real 401) whenever a request lacks an explicit
        // `Accept: application/json` header.
        $middleware->redirectGuestsTo(fn () => null);

        // The container only ever receives traffic from the host platform's
        // own reverse proxy (Render, or docker-compose's port mapping
        // locally) — trusting it is what makes $request->secure(), the
        // url()/route() helpers, and generated https:// links correct
        // behind TLS termination that happens upstream of this process.
        // Without this, Laravel sees a plain-HTTP request even when the
        // real client connected over HTTPS.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'staff' => EnsureIsStaff::class,
            // Sanctum doesn't auto-register these under the streamlined
            // bootstrap/app.php middleware system (only the old HTTP Kernel
            // did) — registered explicitly so `abilities:access-api` /
            // `abilities:refresh-token` on routes actually resolve.
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // PRD §13: every /api/v1/* response is { success, data, error }, with
        // error.code machine-readable and error.message localized/human-readable.
        // Non-API requests (none expected beyond the root health/status routes)
        // keep Laravel's default HTML/debug rendering.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ApiException) {
                return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fieldErrors);
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'validation_error',
                    $e->getMessage(),
                    400,
                    $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('unauthenticated', 'Authentication is required.', 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('forbidden', 'You are not authorized to perform this action.', 403);
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('not_found', 'The requested resource was not found.', 404);
            }

            if ($e instanceof TooManyRequestsHttpException) {
                return ApiResponse::error('rate_limited', 'Too many requests. Please try again later.', 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $code = match ($status) {
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    default => 'http_error',
                };

                return ApiResponse::error($code, $e->getMessage() ?: 'An error occurred.', $status);
            }

            $message = config('app.debug')
                ? $e->getMessage()
                : 'An unexpected error occurred.';

            return ApiResponse::error('server_error', $message, 500);
        });
    })->create();

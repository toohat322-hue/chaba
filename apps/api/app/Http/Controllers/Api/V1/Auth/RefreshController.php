<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        // Rotation: the refresh token just used is revoked (along with its
        // sibling access token, if still alive) before a fresh pair issues,
        // so it can never be replayed.
        $this->tokens->revokePair($user, $token->name);

        return ApiResponse::success($this->tokens->issuePair($user));
    }
}

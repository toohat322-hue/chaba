<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();

        $this->tokens->revokePair($request->user(), $token->name);

        return ApiResponse::success(['message' => 'Logged out.']);
    }
}

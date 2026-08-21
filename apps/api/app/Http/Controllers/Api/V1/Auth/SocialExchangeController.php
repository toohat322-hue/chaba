<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialExchangeRequest;
use App\Services\SocialExchangeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SocialExchangeController extends Controller
{
    public function __construct(private readonly SocialExchangeService $exchange) {}

    /**
     * The only place real access/refresh tokens are ever transmitted for a
     * social login — SocialCallbackController deliberately never puts them
     * in the redirect URL. Replays whatever SocialAuthService::resolve()
     * already decided (a full token pair, or a requires_two_factor shape),
     * it doesn't re-derive an auth decision here.
     */
    public function __invoke(SocialExchangeRequest $request): JsonResponse
    {
        $payload = $this->exchange->redeem($request->string('code')->toString());

        if (! $payload) {
            throw new ApiException('invalid_or_expired_code', 'This sign-in link has expired. Please try again.', 400);
        }

        return ApiResponse::success($payload);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyTwoFactorLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\TokenService;
use App\Services\TwoFactorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Second step of a 2FA login: the client authenticates with the short-lived
 * 'two-factor-pending' token LoginController issued (see its
 * abilities:two-factor-pending route middleware), not with a password.
 */
class TwoFactorLoginController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function __invoke(VerifyTwoFactorLoginRequest $request): JsonResponse
    {
        // LoginController's own response eager-loads 'role' (UserResource's
        // 'role' key is whenLoaded — silently omitted otherwise); $request
        // ->user() here is resolved by Sanctum from the pending token alone
        // and doesn't carry that relation, so it's loaded explicitly to
        // keep this response shaped exactly like LoginController's.
        $user = $request->user()->load('role');

        if (! $this->twoFactor->verifyLoginCode($user, $request->string('code')->toString())) {
            throw new ApiException('two_factor_invalid', 'The code you entered is incorrect.', 400);
        }

        // The pending token's only job was to get here — burn it so it
        // can't be replayed, then issue the real pair.
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success([
            'user' => new UserResource($user),
            ...$this->tokens->issuePair($user),
        ]);
    }
}

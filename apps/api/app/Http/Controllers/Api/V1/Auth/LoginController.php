<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TokenService;
use App\Services\TwoFactorService;
use App\Support\ApiResponse;
use App\Support\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login')->toString();
        $normalizedPhone = PhoneNormalizer::toE164($login);

        $user = $normalizedPhone
            ? User::with('role')->where('phone', $normalizedPhone)->first()
            : User::with('role')->where('email', $login)->first();

        if (! $user || ! $user->password_hash || ! Hash::check($request->string('password')->toString(), $user->password_hash)) {
            throw new ApiException('invalid_credentials', 'The provided credentials are incorrect.', 401);
        }

        if ($user->status === 'blocked') {
            throw new ApiException('account_blocked', 'This account has been blocked.', 403);
        }

        if ($this->twoFactor->isEnabled($user)) {
            return ApiResponse::success([
                'requires_two_factor' => true,
                ...$this->tokens->issuePendingTwoFactorToken($user),
            ]);
        }

        return ApiResponse::success([
            'user' => new UserResource($user),
            ...$this->tokens->issuePair($user),
        ]);
    }
}

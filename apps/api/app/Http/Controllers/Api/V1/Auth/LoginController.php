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
use Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    // Per-account lockout alongside the route's per-IP throttle:10,1 (PRD
    // §15 asks for both) — an attacker rotating IPs still hits this once
    // they've failed enough times against one specific account.
    private const MAX_ATTEMPTS = 10;

    private const LOCKOUT_TTL_SECONDS = 900;

    public function __construct(
        private readonly TokenService $tokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login')->toString();
        $normalizedPhone = PhoneNormalizer::toE164($login);
        $lockoutKey = 'login:lockout:'.($normalizedPhone ?? mb_strtolower($login));

        if (Redis::exists($lockoutKey)) {
            throw new ApiException('account_locked', 'Too many failed attempts. Try again later.', 429);
        }

        $user = $normalizedPhone
            ? User::with('role')->where('phone', $normalizedPhone)->first()
            : User::with('role')->where('email', $login)->first();

        if (! $user || ! $user->password_hash || ! Hash::check($request->string('password')->toString(), $user->password_hash)) {
            $attemptsKey = 'login:attempts:'.($normalizedPhone ?? mb_strtolower($login));
            $attempts = Redis::incr($attemptsKey);
            Redis::expire($attemptsKey, self::LOCKOUT_TTL_SECONDS);

            if ($attempts >= self::MAX_ATTEMPTS) {
                Redis::setex($lockoutKey, self::LOCKOUT_TTL_SECONDS, '1');
                Redis::del($attemptsKey);
            }

            throw new ApiException('invalid_credentials', 'The provided credentials are incorrect.', 401);
        }

        Redis::del('login:attempts:'.($normalizedPhone ?? mb_strtolower($login)));

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

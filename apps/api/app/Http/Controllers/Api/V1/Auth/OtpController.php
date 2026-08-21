<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly TokenService $tokens,
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $purpose = $request->string('purpose')->toString();

        if (in_array($purpose, ['login', 'password_reset'], true) && ! User::where('phone', $phone)->exists()) {
            // Same generic message either way so this endpoint can't be used
            // to enumerate which phone numbers have accounts.
            throw new ApiException('otp_send_failed', 'Unable to send a code for this request.', 422);
        }

        $this->otp->send($phone, $purpose);

        return ApiResponse::success(['message' => 'Verification code sent.']);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $purpose = $request->string('purpose')->toString();
        $code = $request->string('code')->toString();

        if (! $this->otp->verify($phone, $purpose, $code)) {
            throw new ApiException('otp_invalid', 'The code you entered is incorrect.', 400);
        }

        $user = User::with('role')->where('phone', $phone)->first();

        if (! $user) {
            throw new ApiException('not_found', 'No account found for this phone number.', 404);
        }

        if ($purpose === 'verify') {
            $user->forceFill(['phone_verified_at' => now()])->save();

            return ApiResponse::success(['user' => new UserResource($user)]);
        }

        // purpose === 'login': OTP acts as a passwordless login method (PRD §7.1).
        return ApiResponse::success([
            'user' => new UserResource($user),
            ...$this->tokens->issuePair($user),
        ]);
    }
}

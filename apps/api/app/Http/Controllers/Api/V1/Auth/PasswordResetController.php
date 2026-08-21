<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            throw new ApiException('not_found', 'No account found for this phone number.', 404);
        }

        if (! $this->otp->verify($phone, 'password_reset', $request->string('code')->toString())) {
            throw new ApiException('otp_invalid', 'The code you entered is incorrect.', 400);
        }

        $user->forceFill([
            'password_hash' => Hash::make($request->string('new_password')->toString()),
        ])->save();

        // All existing sessions are invalidated on password change (PRD §15).
        $user->tokens()->delete();

        return ApiResponse::success(['message' => 'Password updated. Please log in again.']);
    }
}

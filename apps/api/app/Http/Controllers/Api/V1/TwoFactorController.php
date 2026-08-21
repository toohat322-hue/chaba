<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTwoFactorRequest;
use App\Http\Requests\DisableTwoFactorRequest;
use App\Services\TwoFactorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service 2FA setup for the current account (any authenticated user —
 * the admin UI is the only surface that exposes this today, but the API
 * itself isn't staff-only, matching how ChangePasswordRequest/UserController
 * aren't either).
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success($this->twoFactor->startSetup($request->user()));
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $codes = $this->twoFactor->confirmSetup($request->user(), $request->string('code')->toString());

        if ($codes === null) {
            throw new ApiException('two_factor_invalid', 'The code you entered is incorrect.', 400);
        }

        return ApiResponse::success(['recovery_codes' => $codes]);
    }

    public function destroy(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('password')->toString(), $user->password_hash)) {
            throw ApiException::businessRule('invalid_current_password', 'The current password you entered is incorrect.');
        }

        $this->twoFactor->disable($user);

        return ApiResponse::success(['message' => 'Two-factor authentication disabled.']);
    }
}

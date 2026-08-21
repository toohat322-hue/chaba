<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('role')));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return ApiResponse::success(new UserResource($user));
    }

    /**
     * Revokes every active session (same "revoke everything" pattern
     * Admin\CustomerController::update uses when blocking a customer) —
     * changing your password logs out all devices, including this one.
     */
    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), $user->password_hash)) {
            throw ApiException::businessRule('invalid_current_password', 'The current password you entered is incorrect.');
        }

        $user->forceFill(['password_hash' => Hash::make($request->string('password')->toString())])->save();
        $user->tokens()->delete();

        return ApiResponse::success(['message' => 'Password changed.']);
    }

    /**
     * For a social-only account (password_hash is null) to establish a
     * password as a fallback login method — distinct from updatePassword()
     * above, which requires a current_password to verify and would
     * misbehave (Hash::check against null) for exactly the accounts this
     * exists for. Does not revoke existing sessions, unlike a real password
     * *change*: nothing security-sensitive is being invalidated, since
     * there was no password before this to have leaked.
     */
    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->password_hash) {
            throw ApiException::businessRule('password_already_set', 'This account already has a password — use change password instead.');
        }

        $user->forceFill(['password_hash' => Hash::make($request->string('password')->toString())])->save();

        return ApiResponse::success(['message' => 'Password set.']);
    }
}

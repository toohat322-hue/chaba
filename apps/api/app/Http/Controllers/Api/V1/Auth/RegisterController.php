<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\TokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        // forceFill: is_guest/status aren't mass-assignable on User (see the
        // model) — this is the one legitimate place a brand-new account's
        // status is set.
        $user = new User;
        $user->forceFill([
            'full_name' => $request->string('full_name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->email,
            'password_hash' => Hash::make($request->string('password')->toString()),
            'preferred_language' => $request->input('preferred_language', 'ar'),
            'is_guest' => false,
            'status' => 'active',
        ])->save();

        // Email is optional at registration (phone is the required identity
        // field) — most registrations have no email at all, so this simply
        // doesn't fire for them rather than falling back to any dev inbox.
        if ($user->email) {
            Mail::to($user->email)->send(new WelcomeMail($user));
        }

        return ApiResponse::success([
            'user' => new UserResource($user),
            ...$this->tokens->issuePair($user),
        ], 201);
    }
}

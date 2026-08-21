<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Http\Resources\UserResource;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Resolves a verified social-provider identity into a User, mirroring
 * LoginController's exact decision order (blocked check, then 2FA check,
 * then issue tokens) so social login can never bypass account security that
 * password login enforces. Deliberately Socialite-agnostic — takes plain
 * scalars extracted by the caller, not a Socialite User object, so this
 * class stays easy to unit-test without hitting any provider.
 */
class SocialAuthService
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    /**
     * @return array{user: array, access_token: string, refresh_token: string, token_type: string, expires_in: int}
     *                                                                                                              |array{requires_two_factor: true, two_factor_token: string, expires_in: int}
     */
    public function resolve(
        string $provider,
        string $providerId,
        ?string $email,
        bool $emailVerifiedByProvider,
        ?string $name,
        ?string $linkUserId = null,
    ): array {
        if ($linkUserId) {
            $user = $this->linkToExistingUser($linkUserId, $provider, $providerId, $email);

            return $this->issueSessionFor($user);
        }

        $existingLink = SocialAccount::where('provider', $provider)->where('provider_id', $providerId)->first();

        if ($existingLink) {
            return $this->issueSessionFor($existingLink->user);
        }

        if ($email && $emailVerifiedByProvider) {
            $matchedUser = User::where('email', $email)->first();

            if ($matchedUser) {
                if ($matchedUser->role_id) {
                    // Admin-safety: social login must never grant access to a
                    // staff account. There's no separate admin login route to
                    // wall off (staff and customers share POST /auth/login),
                    // so this data-driven refusal IS the actual guard.
                    throw ApiException::businessRule(
                        'social_login_not_available_for_staff',
                        'Social login is not available for this account.',
                    );
                }

                SocialAccount::create([
                    'user_id' => $matchedUser->id,
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'provider_email' => $email,
                ]);

                return $this->issueSessionFor($matchedUser);
            }
        }

        // No existing link and no matching verified email (or no email at
        // all — e.g. a returning Apple user who didn't resend it, or a
        // provider that genuinely has none) — a brand-new customer account.
        $user = DB::transaction(function () use ($provider, $providerId, $email, $name) {
            // forceFill: role_id/is_guest/status aren't mass-assignable on
            // User (see the model) — this is a legitimate place to set them
            // for a brand-new social-signup account.
            $newUser = new User;
            $newUser->forceFill([
                'full_name' => $name ?: 'CHABA Customer',
                'phone' => null,
                'email' => $email,
                'password_hash' => null,
                'role_id' => null,
                'preferred_language' => 'ar',
                'is_guest' => false,
                'status' => 'active',
            ])->save();

            SocialAccount::create([
                'user_id' => $newUser->id,
                'provider' => $provider,
                'provider_id' => $providerId,
                'provider_email' => $email,
            ]);

            return $newUser;
        });

        return $this->issueSessionFor($user);
    }

    private function linkToExistingUser(string $userId, string $provider, string $providerId, ?string $email): User
    {
        $user = User::findOrFail($userId);

        // Same invariant as the email-match path: never let a link ticket
        // (which can only be minted for an already-authenticated user, see
        // SocialLinkTicketService) attach a social identity to a staff
        // account either.
        if ($user->role_id) {
            throw ApiException::businessRule(
                'social_login_not_available_for_staff',
                'Social login is not available for this account.',
            );
        }

        $alreadyLinkedElsewhere = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($alreadyLinkedElsewhere) {
            throw ApiException::businessRule(
                'social_account_already_linked',
                'This account is already linked to a different CHABA user.',
            );
        }

        SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $providerId],
            ['user_id' => $user->id, 'provider_email' => $email],
        );

        return $user;
    }

    private function issueSessionFor(User $user): array
    {
        if ($user->status === 'blocked') {
            throw new ApiException('account_blocked', 'This account has been blocked.', 403);
        }

        if ($this->twoFactor->isEnabled($user)) {
            return [
                'requires_two_factor' => true,
                ...$this->tokens->issuePendingTwoFactorToken($user),
            ];
        }

        return [
            'user' => (new UserResource($user->loadMissing('role')))->resolve(),
            ...$this->tokens->issuePair($user),
        ];
    }
}

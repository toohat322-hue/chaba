<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\SocialLinkTicketService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'apple'];

    public function __construct(private readonly SocialLinkTicketService $linkTickets) {}

    public function index(Request $request): JsonResponse
    {
        $linked = $request->user()->socialAccounts()->pluck('provider_email', 'provider');

        $accounts = collect(self::PROVIDERS)->map(fn ($provider) => [
            'provider' => $provider,
            'connected' => $linked->has($provider),
            'email' => $linked->get($provider),
        ]);

        return ApiResponse::success(['accounts' => $accounts]);
    }

    public function linkToken(Request $request, string $provider): JsonResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new ApiException('not_found', 'Unknown provider.', 404);
        }

        $ticket = $this->linkTickets->store($request->user());
        $apiUrl = rtrim(config('app.url'), '/');

        return ApiResponse::success([
            'redirect_url' => "{$apiUrl}/api/v1/auth/{$provider}/redirect?".http_build_query(['link_ticket' => $ticket]),
        ]);
    }

    public function destroy(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();
        $account = $user->socialAccounts()->where('provider', $provider)->first();

        if (! $account) {
            throw new ApiException('not_found', 'This provider is not connected to your account.', 404);
        }

        $remainingProviderCount = $user->socialAccounts()->where('provider', '!=', $provider)->count();

        if (! $user->password_hash && $remainingProviderCount === 0) {
            throw ApiException::businessRule(
                'last_login_method',
                'Set a password before disconnecting your only sign-in method, or you could be locked out of your account.',
            );
        }

        $account->delete();

        return ApiResponse::success(['message' => 'Disconnected.']);
    }
}

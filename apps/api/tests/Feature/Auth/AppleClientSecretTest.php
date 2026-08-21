<?php

namespace Tests\Feature\Auth;

use Laravel\Socialite\Facades\Socialite;
use ReflectionClass;
use Tests\TestCase;

/**
 * Apple's client_secret isn't a static value — it's a JWT the
 * socialiteproviders/apple package generates on the fly from team_id/
 * key_id/private_key (AppleToken::generate()). Every other test in this
 * suite mocks Socialite entirely, which never exercises this real code
 * path — this test uses a throwaway in-memory EC keypair (never touches
 * disk, never a real Apple credential) specifically to prove the whole
 * mechanism actually produces a valid, correctly-populated JWT. This also
 * guards config/services.php's apple.client_secret key existing at all —
 * SocialiteProviders\Manager's config resolver requires the key to merely
 * be present (even null) to resolve the driver, a real gap this test
 * caught (MissingConfigException) before the config array had it.
 */
class AppleClientSecretTest extends TestCase
{
    // A throwaway EC keypair generated once via `openssl ecparam -genkey
    // -name prime256v1 -noout` purely so this test has *some* validly-
    // shaped EC private key to sign with — it has never been used for
    // anything else and grants access to nothing; committing it is no
    // different from committing any other test fixture.
    private const TEST_PRIVATE_KEY = <<<'PEM'
    -----BEGIN EC PRIVATE KEY-----
    MHcCAQEEIGLHDvYpOOYoM95KJMT3Z2FYbfQwVHme+5rnGiCJHwUNoAoGCCqGSM49
    AwEHoUQDQgAE4yUOuJDNHO9D/j8L18rejh3dgyn/S+8k4BNWbJvsmJWKJTn15o+8
    IP/Me2QvfnhSsODahnZl45BegyIC2oV1Ww==
    -----END EC PRIVATE KEY-----
    PEM;

    public function test_a_real_client_secret_jwt_is_generated_from_the_configured_private_key(): void
    {
        config([
            'services.apple.enabled' => true,
            'services.apple.client_id' => 'dz.chaba.web',
            'services.apple.team_id' => 'TESTTEAM01',
            'services.apple.key_id' => 'TESTKEY001',
            'services.apple.private_key' => self::TEST_PRIVATE_KEY,
            'services.apple.redirect' => 'http://localhost:8000/api/v1/auth/apple/callback',
        ]);

        $provider = Socialite::driver('apple');
        $method = (new ReflectionClass($provider))->getMethod('getClientSecret');
        $method->setAccessible(true);
        $secret = $method->invoke($provider);

        $segments = explode('.', $secret);
        $this->assertCount(3, $segments, 'client_secret must be a well-formed JWT (header.payload.signature)');

        $header = json_decode(base64_decode(strtr($segments[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($segments[1], '-_', '+/')), true);

        $this->assertSame('ES256', $header['alg']);
        $this->assertSame('TESTKEY001', $header['kid']);
        $this->assertSame('TESTTEAM01', $payload['iss']);
        $this->assertSame('dz.chaba.web', $payload['sub']);
        $this->assertSame('https://appleid.apple.com', $payload['aud']);
    }
}

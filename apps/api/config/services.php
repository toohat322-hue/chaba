<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // 'log' (default) writes SMS text to the log + a dev inbox (Mailhog) so
    // auth is testable with zero external setup. Set SMS_DRIVER=twilio and
    // the twilio.* values below once a real Twilio account exists —
    // TwilioSmsDriver picks them up automatically, no code changes needed.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'dev_inbox' => env('SMS_DEV_INBOX', 'otp-dev@chaba.dz'),
        'twilio' => [
            'sid' => env('TWILIO_ACCOUNT_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM_NUMBER'),
        ],
    ],

    // PRD §7.9/§25 Phase 6: online payment gateways. No merchant credentials
    // or API documentation exist for either yet — both stay disabled
    // (`enabled` false) until real values are supplied here, at which point
    // App\Services\Payments\PaymentGatewayResolver picks them up automatically
    // (see AppServiceProvider::register()) with no code changes needed.
    'cib' => [
        'enabled' => env('CIB_ENABLED', false),
        'merchant_id' => env('CIB_MERCHANT_ID'),
        'api_key' => env('CIB_API_KEY'),
        'secret' => env('CIB_SECRET'),
        'base_url' => env('CIB_BASE_URL'),
    ],

    'edahabia' => [
        'enabled' => env('EDAHABIA_ENABLED', false),
        'merchant_id' => env('EDAHABIA_MERCHANT_ID'),
        'api_key' => env('EDAHABIA_API_KEY'),
        'secret' => env('EDAHABIA_SECRET'),
        'base_url' => env('EDAHABIA_BASE_URL'),
    ],

    // Social login (Google/Facebook/Apple). Same "stays disabled until real
    // values exist" convention as cib/edahabia above — SocialRedirectController
    // checks `enabled` first and fails cleanly rather than letting Socialite
    // throw on empty credentials. client_id/client_secret/redirect are the
    // exact key names Laravel Socialite's SocialiteManager reads from
    // config('services.{provider}') — don't rename them.
    'google' => [
        'enabled' => env('GOOGLE_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'enabled' => env('FACEBOOK_ENABLED', false),
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    // Apple has no long-lived client_secret to paste in — it's a short-lived
    // JWT that must be *generated* from these values (team_id, key_id, the
    // private key) each time Socialite needs one. See
    // SocialRedirectController/SocialCallbackController for where
    // SocialiteProviders\Apple\ClientSecretGenerator builds it on the fly.
    'apple' => [
        'enabled' => env('APPLE_ENABLED', false),
        'client_id' => env('APPLE_CLIENT_ID'), // the Services ID identifier, e.g. dz.chaba.web
        // Left null — Socialite's manager package requires this key to
        // merely *exist* here (array_key_exists, not truthiness) to resolve
        // the driver at all, but the real value is generated at runtime
        // from team_id/key_id/private_key below (see AppleToken::generate())
        // and never set by hand.
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'), // PEM contents of the .p8 key, not a file path
    ],

];

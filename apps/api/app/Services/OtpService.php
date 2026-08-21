<?php

namespace App\Services;

use App\Events\OtpGenerated;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Redis;

/**
 * PRD §7.1/§15: OTP codes expire in 5 minutes, max 5 verify attempts, then a
 * 15-minute lockout; sending is rate-limited to 1/minute per phone. Storage
 * is Redis (already provisioned), not a DB table — OTPs are inherently
 * short-lived and Redis TTL/counters model that natively without adding
 * schema the PRD's §12 design doesn't have.
 *
 * Delivery goes through SmsService, whose driver is picked by SMS_DRIVER
 * ('log' by default — see LogSmsDriver — or 'twilio' once real credentials
 * are supplied).
 */
class OtpService
{
    private const CODE_TTL_SECONDS = 300;

    private const RATE_LIMIT_TTL_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_TTL_SECONDS = 900;

    public function __construct(private readonly SmsService $sms) {}

    public function send(string $phone, string $purpose): void
    {
        $rateLimitKey = $this->key('ratelimit', $purpose, $phone);

        if (Redis::exists($rateLimitKey)) {
            throw new ApiException('rate_limited', 'Please wait before requesting another code.', 429);
        }

        $code = (string) random_int(100000, 999999);

        Redis::setex($this->key('code', $purpose, $phone), self::CODE_TTL_SECONDS, hash('sha256', $code));
        Redis::del($this->key('attempts', $purpose, $phone));
        Redis::setex($rateLimitKey, self::RATE_LIMIT_TTL_SECONDS, '1');

        OtpGenerated::dispatch($phone, $purpose, $code);
        $this->deliver($phone, $code, $purpose);
    }

    public function verify(string $phone, string $purpose, string $code): bool
    {
        $lockoutKey = $this->key('lockout', $purpose, $phone);

        if (Redis::exists($lockoutKey)) {
            throw new ApiException('otp_locked', 'Too many incorrect attempts. Try again later.', 429);
        }

        $codeKey = $this->key('code', $purpose, $phone);
        $storedHash = Redis::get($codeKey);

        if (! $storedHash) {
            throw new ApiException('otp_expired', 'This code has expired. Please request a new one.', 400);
        }

        if (! hash_equals($storedHash, hash('sha256', $code))) {
            $attemptsKey = $this->key('attempts', $purpose, $phone);
            $attempts = Redis::incr($attemptsKey);
            Redis::expire($attemptsKey, self::CODE_TTL_SECONDS);

            if ($attempts >= self::MAX_ATTEMPTS) {
                Redis::setex($lockoutKey, self::LOCKOUT_TTL_SECONDS, '1');
                Redis::del($codeKey, $attemptsKey);
            }

            return false;
        }

        Redis::del($codeKey, $this->key('attempts', $purpose, $phone));

        return true;
    }

    private function deliver(string $phone, string $code, string $purpose): void
    {
        $this->sms->send($phone, "CHABA verification code: {$code}. Valid for 5 minutes.");
    }

    private function key(string $type, string $purpose, string $phone): string
    {
        return "otp:{$type}:{$purpose}:{$phone}";
    }
}

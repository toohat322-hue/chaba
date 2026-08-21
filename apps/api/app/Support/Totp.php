<?php

namespace App\Support;

/**
 * RFC 6238 TOTP (SHA-1, 6 digits, 30s step) implemented directly — no
 * pragmarx/google2fa or similar composer package needed for an algorithm
 * this small and this precisely specified. Compatible with any standard
 * authenticator app (Google Authenticator, Authy, 1Password, ...).
 */
class Totp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $secret, string $accountLabel, string $issuer = 'CHABA'): string
    {
        $label = rawurlencode("{$issuer}:{$accountLabel}");
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * $window=1 accepts the previous/current/next 30s step, tolerating
     * ~30s of clock drift between the server and the user's phone.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::generateCode($secret, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function generateCode(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($truncated % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    private static function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper(rtrim($data, '='));
        $binaryString = '';

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $bytes .= chr(bindec($byte));
        }

        return $bytes;
    }
}

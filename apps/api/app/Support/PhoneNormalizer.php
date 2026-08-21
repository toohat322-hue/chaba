<?php

namespace App\Support;

/**
 * Algerian mobile format (PRD §7.8): 0[5-7]XXXXXXXX (10 digits) entered by the
 * user, stored internationally as +213[5-7]XXXXXXXX. Used by both the
 * AlgerianPhone validation rule and the auth controllers so normalization
 * happens in exactly one place.
 */
class PhoneNormalizer
{
    private const LOCAL_PATTERN = '/^0[5-7]\d{8}$/';

    private const E164_PATTERN = '/^\+213[5-7]\d{8}$/';

    public static function toE164(string $phone): ?string
    {
        $phone = trim($phone);

        if (preg_match(self::E164_PATTERN, $phone)) {
            return $phone;
        }

        if (preg_match(self::LOCAL_PATTERN, $phone)) {
            return '+213'.substr($phone, 1);
        }

        return null;
    }

    public static function isValid(string $phone): bool
    {
        return self::toE164($phone) !== null;
    }
}

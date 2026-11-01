<?php

namespace App\Support;

/**
 * US phone numbers, stored as "(123) 123-9090".
 *
 * Anything that carries ten digits (optionally behind a leading country code
 * 1) can be formatted; everything else is left to the caller to reject or keep.
 */
class PhoneNumber
{
    /** The ten national digits, or null when the value is not a US number. */
    public static function digits(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 10 ? $digits : null;
    }

    /** "(123) 123-9090", or null when the value is not a US number. */
    public static function format(?string $value): ?string
    {
        $digits = self::digits($value);

        if ($digits === null) {
            return null;
        }

        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }

    /** Formatted when it can be, otherwise the value exactly as given. */
    public static function formatOrKeep(?string $value): ?string
    {
        return self::format($value) ?? $value;
    }
}

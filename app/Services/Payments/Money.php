<?php

namespace App\Services\Payments;

/**
 * Tiny helper so amounts are always compared as integer piasters (no float drift,
 * no bcmath dependency).
 */
final class Money
{
    public static function cents(float|int|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) str_replace(',', '', (string) $value)) * 100);
    }

    /** 2-decimal string, never negative. */
    public static function format(float|int|string|null $value): string
    {
        return number_format(max(0, self::cents($value)) / 100, 2, '.', '');
    }

    public static function equals(float|int|string|null $a, float|int|string|null $b): bool
    {
        return self::cents($a) === self::cents($b);
    }

    public static function isPositive(float|int|string|null $value): bool
    {
        return self::cents($value) > 0;
    }
}

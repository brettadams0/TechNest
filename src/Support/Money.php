<?php

declare(strict_types=1);

namespace TechNest\Support;

use InvalidArgumentException;

/**
 * Money is held as integer cents everywhere — in PHP, in the database and in
 * the session. Floats cannot represent 0.10 exactly, and a store that adds up
 * prices in floating point eventually charges someone $1,199.9899999.
 */
final class Money
{
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return sprintf('%s$%s.%02d', $sign, number_format(intdiv($cents, 100)), $cents % 100);
    }

    /** Whole-dollar amounts without cents, for prose: 10000 → "$100". */
    public static function formatShort(int $cents): string
    {
        return $cents % 100 === 0 ? '$' . number_format(intdiv($cents, 100)) : self::format($cents);
    }

    /** Plain decimal for form fields: 119999 → "1199.99". */
    public static function toDecimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Parse a non-negative amount typed by an admin ("1199.99", "1,199.99",
     * "$15", "0.5") into cents, without passing through a float.
     */
    public static function parse(string $input): int
    {
        $clean = str_replace([',', '$', ' '], '', trim($input));

        if (preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $clean, $m) !== 1) {
            throw new InvalidArgumentException('Enter an amount like 19.99');
        }

        $fraction = str_pad($m[2] ?? '', 2, '0');

        return (int) $m[1] * 100 + (int) $fraction;
    }
}

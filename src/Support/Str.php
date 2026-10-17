<?php

declare(strict_types=1);

namespace TechNest\Support;

final class Str
{
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    public static function limit(string $value, int $length): string
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return rtrim(substr($value, 0, $length - 1)) . '…';
    }
}

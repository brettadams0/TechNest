<?php

declare(strict_types=1);

namespace TechNest\Database;

use UnexpectedValueException;

/**
 * Typed reads from a database row.
 *
 * Drivers disagree about column types — SQLite returns integers, MySQL
 * returns SUM() as a numeric string — so every row read goes through here:
 * values are normalised to the type the code expects, and anything that
 * cannot be (a NULL id, a word where a number belongs) fails loudly instead
 * of being silently cast to 0 or "".
 */
final class Row
{
    /** @param array<string, mixed> $row */
    public static function int(array $row, string $column): int
    {
        $value = $row[$column] ?? null;

        return match (true) {
            is_int($value) => $value,
            is_bool($value) => (int) $value,
            is_string($value) && preg_match('/^-?\d+(?:\.0+)?$/', $value) === 1 => (int) $value,
            is_float($value) && floor($value) === $value => (int) $value,
            default => throw new UnexpectedValueException("Column '{$column}' is not an integer."),
        };
    }

    /** @param array<string, mixed> $row */
    public static function string(array $row, string $column): string
    {
        $value = $row[$column] ?? null;

        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            default => throw new UnexpectedValueException("Column '{$column}' is not a string."),
        };
    }

    /** @param array<string, mixed> $row */
    public static function nullableString(array $row, string $column): ?string
    {
        return ($row[$column] ?? null) === null ? null : self::string($row, $column);
    }

    /** @param array<string, mixed> $row */
    public static function bool(array $row, string $column): bool
    {
        return self::int($row, $column) !== 0;
    }
}

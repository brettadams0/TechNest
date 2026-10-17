<?php

declare(strict_types=1);

namespace TechNest\Support;

use InvalidArgumentException;

/**
 * Collects cleaned values and error messages for one submitted form. Each
 * method returns the cleaned value (or a safe default) and records an error
 * against the field if it is invalid, so a controller can validate a whole
 * form and then check fails() once.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data) {}

    public function string(string $field, string $label, int $min = 1, int $max = 255, ?string $pattern = null, string $patternMessage = ''): string
    {
        $value = trim($this->raw($field));
        $length = mb_strlen($value);

        if ($value === '' && $min > 0) {
            $this->addError($field, "{$label} is required.");
        } elseif ($length < $min) {
            $this->addError($field, "{$label} must be at least {$min} characters.");
        } elseif ($length > $max) {
            $this->addError($field, "{$label} must be at most {$max} characters.");
        } elseif ($pattern !== null && $value !== '' && preg_match($pattern, $value) !== 1) {
            $this->addError($field, $patternMessage !== '' ? $patternMessage : "{$label} is not valid.");
        }

        return $value;
    }

    public function email(string $field, string $label = 'Email'): string
    {
        $value = strtolower(trim($this->raw($field)));

        if ($value === '') {
            $this->addError($field, "{$label} is required.");
        } elseif (strlen($value) > 190 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, 'Enter a valid email address.');
        }

        return $value;
    }

    /** Passwords are never trimmed: leading and trailing spaces are characters too. */
    public function password(string $field, int $min = 10, string $label = 'Password'): string
    {
        $value = $this->raw($field);

        if ($value === '') {
            $this->addError($field, "{$label} is required.");
        } elseif (strlen($value) < $min) {
            $this->addError($field, "{$label} must be at least {$min} characters.");
        } elseif (strlen($value) > 72) {
            // bcrypt silently ignores everything after 72 bytes.
            $this->addError($field, "{$label} must be at most 72 characters.");
        }

        return $value;
    }

    public function matches(string $field, string $other, string $message): void
    {
        if ($this->raw($field) !== $this->raw($other)) {
            $this->addError($field, $message);
        }
    }

    public function int(string $field, string $label, int $min, int $max): int
    {
        $value = trim($this->raw($field));

        if (preg_match('/^-?\d{1,9}$/', $value) !== 1) {
            $this->addError($field, "{$label} must be a whole number.");

            return $min;
        }

        $int = (int) $value;
        if ($int < $min || $int > $max) {
            $this->addError($field, "{$label} must be between {$min} and {$max}.");
        }

        return $int;
    }

    public function money(string $field, string $label): int
    {
        try {
            return Money::parse($this->raw($field));
        } catch (InvalidArgumentException) {
            $this->addError($field, "{$label} must be an amount like 19.99.");

            return 0;
        }
    }

    /**
     * @template T of string
     *
     * @param array<T, mixed> $allowed keys are the accepted values
     *
     * @return T|''
     */
    public function oneOf(string $field, string $label, array $allowed): string
    {
        $value = trim($this->raw($field));
        if (!array_key_exists($value, $allowed)) {
            $this->addError($field, "Choose a {$label}.");

            return '';
        }

        return $value;
    }

    public function checkbox(string $field): bool
    {
        return $this->raw($field) === '1';
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function raw(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}

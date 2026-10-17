<?php

declare(strict_types=1);

namespace TechNest;

use RuntimeException;

/**
 * Application configuration, read from the environment.
 *
 * Nothing secret has a default. SQLite needs no credentials, so it is the
 * zero-configuration default for local use; MySQL refuses to start until the
 * variables it needs are set, and says which ones are missing. (An earlier
 * version of this project shipped real database credentials as getenv()
 * fallbacks in a public repository — this class exists partly so that cannot
 * happen again.)
 */
final class Config
{
    /** @param array<string, string> $values */
    private function __construct(private readonly array $values) {}

    /**
     * @param array<string, string> $overrides applied on top of the
     *                                         environment; used by tests
     */
    public static function fromEnvironment(string $rootDir, array $overrides = []): self
    {
        $values = self::readDotEnv($rootDir . '/.env');

        foreach (getenv() as $key => $value) {
            $values[$key] = $value;
        }

        return new self(array_merge($values, $overrides));
    }

    /** @param array<string, string> $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * Minimal .env reader: KEY=VALUE lines, # comments, optional quotes.
     * Real environment variables always win over the file.
     *
     * @return array<string, string>
     */
    public static function readDotEnv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (preg_match('/^(["\'])(.*)\1$/', $value, $m) === 1) {
                $value = $m[2];
            }

            $values[$key] = $value;
        }

        return $values;
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? '';

        return $value === '' ? $default : $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = strtolower($this->get($key));

        return $value === '' ? $default : in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public function env(): string
    {
        return $this->get('APP_ENV', 'local');
    }

    public function isProduction(): bool
    {
        return $this->env() === 'production';
    }

    public function debug(): bool
    {
        return $this->bool('APP_DEBUG', !$this->isProduction());
    }

    public function dbDriver(): string
    {
        $driver = $this->get('DB_DRIVER', 'sqlite');
        if (!in_array($driver, ['sqlite', 'mysql'], true)) {
            throw new RuntimeException("DB_DRIVER must be 'sqlite' or 'mysql', got '{$driver}'.");
        }

        return $driver;
    }

    /** @throws RuntimeException naming every missing variable */
    public function requireMysqlCredentials(): void
    {
        $missing = array_values(array_filter(
            ['DB_NAME', 'DB_USER'],
            fn(string $key): bool => $this->get($key) === '',
        ));

        if ($missing !== []) {
            throw new RuntimeException(
                'MySQL is selected but not configured: set ' . implode(' and ', $missing)
                . ' (and DB_PASS) in the environment or in .env — see .env.example.',
            );
        }
    }
}

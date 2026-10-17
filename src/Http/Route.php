<?php

declare(strict_types=1);

namespace TechNest\Http;

/**
 * One entry in the route table. The guard is part of the route, not something
 * a controller remembers to check, so the test suite can enumerate every
 * admin route and prove each one is protected.
 */
final class Route
{
    public const GUARD_NONE = 'none';
    public const GUARD_GUEST = 'guest';
    public const GUARD_AUTH = 'auth';
    public const GUARD_ADMIN = 'admin';

    private readonly string $regex;

    /** @param array{0: class-string, 1: string} $handler */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly array $handler,
        public readonly string $guard = self::GUARD_NONE,
    ) {
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $pattern,
        );
        $this->regex = '#^' . $regex . '$#';
    }

    /** @return array<string, string>|null route parameters, or null if no match */
    public function match(string $path): ?array
    {
        if (preg_match($this->regex, $path, $m) !== 1) {
            return null;
        }

        return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Security;

/**
 * Validates "where to go after login" targets. Only same-site paths are
 * allowed: "/checkout" passes, while "https://evil.example", "//evil.example"
 * (protocol-relative) and "/\evil.example" (which some browsers normalise to
 * a host) all fall back to the default. Without this, a login link carrying
 * ?next= is an open redirect usable in phishing.
 */
final class SafeRedirect
{
    public static function localPath(?string $target, string $fallback = '/'): string
    {
        if ($target === null || $target === '') {
            return $fallback;
        }

        if (!str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\')) {
            return $fallback;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return $fallback;
        }

        return $target;
    }
}

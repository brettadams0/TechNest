<?php

declare(strict_types=1);

namespace TechNest\Security;

/**
 * Synchroniser-token CSRF protection. One random token per session, embedded
 * in every form and compared in constant time on every non-GET request.
 *
 * The check runs in Application::handle() before routing reaches any
 * controller, so a new form cannot forget it: there is no per-route opt-in.
 */
final class Csrf
{
    public const FIELD = '_token';
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session) {}

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function isValid(string $submitted): bool
    {
        $expected = $this->session->get(self::KEY);

        return is_string($expected) && $expected !== '' && hash_equals($expected, $submitted);
    }

    /** New token after login/logout, alongside the new session id. */
    public function rotate(): void
    {
        $this->session->remove(self::KEY);
    }
}

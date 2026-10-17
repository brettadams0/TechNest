<?php

declare(strict_types=1);

namespace TechNest\Security;

use RuntimeException;

final class NativeSession implements Session
{
    public function __construct(bool $secureCookie)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('technest_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Could not start the session.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}

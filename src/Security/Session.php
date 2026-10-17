<?php

declare(strict_types=1);

namespace TechNest\Security;

/**
 * Session storage behind an interface: NativeSession in production,
 * ArraySession in tests. Nothing else in the app touches $_SESSION.
 */
interface Session
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /** Read and remove in one step (flash messages, intended URL). */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Issue a new session id, keeping the data. Called on every privilege
     * change (login, logout) so a session id planted before login is
     * worthless after it — the fix for session fixation.
     */
    public function regenerate(): void;

    /** Drop all data and issue a new id. */
    public function invalidate(): void;
}

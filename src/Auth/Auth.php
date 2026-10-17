<?php

declare(strict_types=1);

namespace TechNest\Auth;

use TechNest\Security\Csrf;
use TechNest\Security\LoginThrottle;
use TechNest\Security\Session;
use TechNest\User\User;
use TechNest\User\UserRepository;

/**
 * Who is making this request. The user is re-read from the database on every
 * request rather than trusted from the session, so disabling an account or
 * revoking admin rights takes effect on that user's very next click.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    /**
     * A real bcrypt hash of a throwaway string. Unknown usernames are checked
     * against it so that "no such user" takes as long as "wrong password" and
     * response timing does not reveal which accounts exist.
     */
    private const TIMING_HASH = '$2y$10$Fr.b8kllN/pq3xsxoTQAVeJGMsgH6R/ZtvBntkzzYiDlxF9hAdd3K';

    private ?User $user = null;
    private bool $resolved = false;
    private ?bool $admin = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
        private readonly LoginThrottle $throttle,
        private readonly Csrf $csrf,
    ) {}

    public function attempt(string $login, string $password, string $ip): LoginResult
    {
        $wait = $this->throttle->secondsUntilAllowed($login, $ip);
        if ($wait > 0) {
            return LoginResult::throttled($wait);
        }

        $found = $this->users->findForLogin($login);
        if ($found === null) {
            password_verify($password, self::TIMING_HASH);
            $this->throttle->recordFailure($login, $ip);

            return LoginResult::invalid();
        }

        [$user, $hash] = $found;
        if (!password_verify($password, $hash)) {
            $this->throttle->recordFailure($login, $ip);

            return LoginResult::invalid();
        }

        // Only reveal "disabled" to someone who proved they know the password.
        if (!$user->isActive) {
            return LoginResult::disabled();
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        }

        $this->throttle->clear($login, $ip);
        $this->login($user);

        return LoginResult::success($user);
    }

    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->csrf->rotate();
        $this->session->set(self::SESSION_KEY, $user->id);
        $this->user = $user;
        $this->resolved = true;
        $this->admin = null;
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->user = null;
        $this->resolved = true;
        $this->admin = false;
    }

    public function user(): ?User
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        $id = $this->session->get(self::SESSION_KEY);
        if (!is_int($id)) {
            return null;
        }

        $user = $this->users->find($id);
        if ($user === null || !$user->isActive) {
            // Account deleted or disabled since login: end the session now.
            $this->logout();

            return null;
        }

        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user()?->id;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $this->admin ??= $this->users->isAdmin($user->id);
    }

    public function verifyPassword(User $user, string $password): bool
    {
        $hash = $this->users->passwordHash($user->id);

        return $hash !== null && password_verify($password, $hash);
    }
}

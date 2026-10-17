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
 * request rather than trusted from the session, so disabling an account,
 * revoking admin rights or changing the password takes effect on every
 * other session of that account at its very next click.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';
    private const SESSION_VERSION_KEY = 'session_version';

    /**
     * A real bcrypt hash of a throwaway string. Unknown usernames are checked
     * against it so that "no such user" takes as long as "wrong password" and
     * response timing does not reveal which accounts exist. Its cost is
     * rewritten to the runtime default by timingHash(): PHP 8.4 raised that
     * default from 10 to 12, and a fixed cost-10 dummy would then answer four
     * times faster than a real account.
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
        // Throttle the account the login resolves to, not the text typed:
        // the database matches several spellings to one row.
        $found = $this->users->findForLogin($login);
        $scope = $found === null ? LoginThrottle::inputScope($login) : LoginThrottle::accountScope($found[0]->id);

        $wait = $this->throttle->reserve($scope, $ip);
        if ($wait > 0) {
            return LoginResult::throttled($wait);
        }

        if ($found === null) {
            password_verify($password, self::timingHash());

            return LoginResult::invalid();
        }

        [$user, $hash] = $found;
        if (!password_verify($password, $hash)) {
            return LoginResult::invalid();
        }

        $this->throttle->clear($scope, $ip);

        // Only reveal "disabled" to someone who proved they know the password.
        if (!$user->isActive) {
            return LoginResult::disabled();
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        }

        $this->login($user);

        return LoginResult::success($user);
    }

    /**
     * Re-check a signed-in user's password (before changing it), through the
     * same throttle as login: otherwise anyone holding a hijacked session
     * could brute-force the password here without limit.
     */
    public function confirmPassword(User $user, string $password, string $ip): LoginResult
    {
        $scope = LoginThrottle::accountScope($user->id);
        $wait = $this->throttle->reserve($scope, $ip);
        if ($wait > 0) {
            return LoginResult::throttled($wait);
        }

        $hash = $this->users->passwordHash($user->id);
        if ($hash === null || !password_verify($password, $hash)) {
            return LoginResult::invalid();
        }

        $this->throttle->clear($scope, $ip);

        return LoginResult::success($user);
    }

    /** The dummy hash at the cost PHP currently uses for real ones. */
    public static function timingHash(): string
    {
        return sprintf('$2y$%02d$', PASSWORD_BCRYPT_DEFAULT_COST) . substr(self::TIMING_HASH, 7);
    }

    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->csrf->rotate();
        $this->session->set(self::SESSION_KEY, $user->id);
        $this->session->set(self::SESSION_VERSION_KEY, $user->sessionVersion);
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
        if ($user === null || !$user->isActive || $this->session->get(self::SESSION_VERSION_KEY) !== $user->sessionVersion) {
            // Account deleted or disabled, or its password changed elsewhere,
            // since this session signed in: end it now.
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
}

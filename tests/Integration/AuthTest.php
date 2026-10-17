<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Auth\Auth;
use TechNest\Auth\LoginOutcome;
use TechNest\Security\ArraySession;
use TechNest\Security\Csrf;
use TechNest\Security\LoginThrottle;
use TechNest\Tests\Support\DatabaseTestCase;
use TechNest\Tests\Support\Fixtures;
use TechNest\User\UserRepository;

final class AuthTest extends DatabaseTestCase
{
    private ArraySession $session;
    private UserRepository $users;
    private LoginThrottle $throttle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
        $this->users = new UserRepository($this->db, $this->clock);
        $this->throttle = new LoginThrottle($this->db, $this->clock);
    }

    public function testSuccessfulLoginRegeneratesTheSessionAndRotatesCsrf(): void
    {
        $user = $this->fixtures->user('dana');
        $csrf = new Csrf($this->session);
        $tokenBefore = $csrf->token();

        $result = $this->auth()->attempt('dana', Fixtures::PASSWORD, '198.51.100.1');

        self::assertSame(LoginOutcome::Success, $result->outcome);
        self::assertSame($user->id, $this->session->get('user_id'));
        self::assertSame(1, $this->session->regenerations, 'Session fixation defence');
        self::assertNotSame($tokenBefore, $csrf->token());
    }

    public function testEmailWorksAsTheLoginName(): void
    {
        $this->fixtures->user('erin');

        self::assertTrue($this->auth()->attempt('erin@example.com', Fixtures::PASSWORD, '198.51.100.1')->succeeded());
    }

    public function testUnknownUserAndWrongPasswordLookIdentical(): void
    {
        $this->fixtures->user('frank');

        $unknown = $this->auth()->attempt('nobody', 'whatever-password', '198.51.100.1');
        $wrong = $this->auth()->attempt('frank', 'wrong-password', '198.51.100.1');

        self::assertSame($unknown->message(), $wrong->message());
        self::assertNull($this->session->get('user_id'));
    }

    public function testDisabledAccountsCannotSignIn(): void
    {
        // Regression: the original login never looked at is_active.
        $this->fixtures->user('gina', active: false);

        $result = $this->auth()->attempt('gina', Fixtures::PASSWORD, '198.51.100.1');

        self::assertSame(LoginOutcome::Disabled, $result->outcome);
        self::assertNull($this->session->get('user_id'));
    }

    public function testDisablingAnAccountEndsItsExistingSession(): void
    {
        $user = $this->fixtures->user('hank');
        $this->auth()->attempt('hank', Fixtures::PASSWORD, '198.51.100.1');

        $this->users->setActive($user->id, false);

        self::assertNull($this->auth()->user(), 'Next request sees no user');
        self::assertNull($this->session->get('user_id'));
    }

    public function testAdminRightsComeFromTheAdminsTable(): void
    {
        $user = $this->fixtures->user('ivy');
        $this->session->set('user_id', $user->id);
        $this->session->set('session_version', $user->sessionVersion);
        self::assertFalse($this->auth()->isAdmin());

        $this->users->grantAdmin($user->id, null);
        self::assertTrue($this->auth()->isAdmin());

        $this->users->revokeAdmin($user->id);
        self::assertFalse($this->auth()->isAdmin());
    }

    public function testFailuresAreThrottled(): void
    {
        $this->fixtures->user('jack');
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            $this->auth()->attempt('jack', 'wrong-password', '198.51.100.1');
        }

        // Even the right password is refused while locked.
        $result = $this->auth()->attempt('jack', Fixtures::PASSWORD, '198.51.100.1');

        self::assertSame(LoginOutcome::Throttled, $result->outcome);
        self::assertSame('Too many failed attempts. Try again in 15 minutes.', $result->message());
    }

    public function testUsernameAndEmailShareOneThrottle(): void
    {
        // Regression: each spelling of the login used to get its own five
        // guesses, and MySQL's collation accepts many spellings.
        $this->fixtures->user('kate');
        foreach (['kate', 'KATE', 'kate@example.com', 'Kate', 'KATE@EXAMPLE.COM'] as $spelling) {
            self::assertSame(LoginOutcome::InvalidCredentials, $this->auth()->attempt($spelling, 'wrong-password', '198.51.100.1')->outcome);
        }

        self::assertSame(LoginOutcome::Throttled, $this->auth()->attempt('kate', Fixtures::PASSWORD, '198.51.100.1')->outcome);
    }

    public function testASuccessfulLoginClearsEarlierFailures(): void
    {
        $this->fixtures->user('liam');
        for ($i = 1; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            $this->auth()->attempt('liam', 'wrong-password', '198.51.100.1');
        }
        self::assertTrue($this->auth()->attempt('liam', Fixtures::PASSWORD, '198.51.100.1')->succeeded());

        self::assertSame(LoginOutcome::InvalidCredentials, $this->auth()->attempt('liam', 'wrong-password', '198.51.100.1')->outcome);
    }

    public function testChangingThePasswordEndsOtherSessions(): void
    {
        $user = $this->fixtures->user('mia');
        $this->auth()->attempt('mia', Fixtures::PASSWORD, '198.51.100.1');
        self::assertNotNull($this->auth()->user());

        // Changed from another browser, e.g. by the owner after a theft.
        $this->users->changePassword($user->id, password_hash('a-new-password', PASSWORD_DEFAULT));

        self::assertNull($this->auth()->user());
        self::assertNull($this->session->get('user_id'));
    }

    public function testConfirmingThePasswordIsThrottled(): void
    {
        $user = $this->fixtures->user('ned');
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            self::assertSame(LoginOutcome::InvalidCredentials, $this->auth()->confirmPassword($user, 'wrong-password', '198.51.100.1')->outcome);
        }

        self::assertSame(LoginOutcome::Throttled, $this->auth()->confirmPassword($user, Fixtures::PASSWORD, '198.51.100.1')->outcome);
        self::assertSame(LoginOutcome::Throttled, $this->auth()->attempt('ned', Fixtures::PASSWORD, '198.51.100.1')->outcome, 'The same budget as login');
    }

    public function testTheTimingHashUsesTheRuntimeBcryptCost(): void
    {
        // PHP 8.4 raised the default cost; a fixed-cost dummy would answer
        // unknown usernames measurably faster than real ones.
        $info = password_get_info(Auth::timingHash());

        self::assertSame(PASSWORD_BCRYPT, $info['algo']);
        self::assertSame(PASSWORD_BCRYPT_DEFAULT_COST, $info['options']['cost'] ?? null);
        self::assertFalse(password_needs_rehash(Auth::timingHash(), PASSWORD_DEFAULT));
    }

    private function auth(): Auth
    {
        // A fresh instance per call, like a fresh request.
        return new Auth($this->session, $this->users, $this->throttle, new Csrf($this->session));
    }
}

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

    private function auth(): Auth
    {
        // A fresh instance per call, like a fresh request.
        return new Auth($this->session, $this->users, $this->throttle, new Csrf($this->session));
    }
}

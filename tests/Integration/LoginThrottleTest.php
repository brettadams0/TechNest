<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Security\LoginThrottle;
use TechNest\Tests\Support\DatabaseTestCase;

final class LoginThrottleTest extends DatabaseTestCase
{
    private LoginThrottle $throttle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->throttle = new LoginThrottle($this->db, $this->clock);
    }

    public function testLocksAfterTheFifthFailureWithinTheWindow(): void
    {
        for ($i = 1; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            $this->throttle->recordFailure('kim', '198.51.100.9');
            $this->clock->advance('+1 minute');
        }
        self::assertSame(0, $this->throttle->secondsUntilAllowed('kim', '198.51.100.9'));

        $this->throttle->recordFailure('kim', '198.51.100.9');

        // The first failure was 4 minutes ago, so it ages out in 11.
        self::assertSame(11 * 60, $this->throttle->secondsUntilAllowed('kim', '198.51.100.9'));
    }

    public function testUnlocksWhenTheOldestFailureLeavesTheWindow(): void
    {
        $this->failTimes(5, 'lee', '198.51.100.9');
        $this->clock->advance('+15 minutes');

        self::assertSame(0, $this->throttle->secondsUntilAllowed('lee', '198.51.100.9'));
    }

    public function testIsScopedToTheScopeAndIpTogether(): void
    {
        $this->failTimes(5, LoginThrottle::inputScope('mo'), '198.51.100.9');

        self::assertGreaterThan(0, $this->throttle->secondsUntilAllowed(LoginThrottle::inputScope(' MO '), '198.51.100.9'), 'Typed logins are normalised');
        self::assertSame(0, $this->throttle->secondsUntilAllowed(LoginThrottle::inputScope('mo'), '203.0.113.50'), 'A different IP cannot lock the user out');
        self::assertSame(0, $this->throttle->secondsUntilAllowed(LoginThrottle::inputScope('other'), '198.51.100.9'));
        self::assertSame(0, $this->throttle->secondsUntilAllowed(LoginThrottle::accountScope(1), '198.51.100.9'));
    }

    public function testReservationsCountBeforeTheOutcomeIsKnown(): void
    {
        // Five requests reserve and are still inside bcrypt: none has
        // recorded a failure yet, but a sixth must not get through.
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            self::assertSame(0, $this->throttle->reserve('pat', '198.51.100.9'));
        }

        self::assertSame(15 * 60, $this->throttle->reserve('pat', '198.51.100.9'));
        self::assertSame(LoginThrottle::MAX_FAILURES, $this->db->fetchInt('SELECT COUNT(*) FROM login_attempts'), 'A refused attempt does not extend the lock');
    }

    public function testASuccessfulReservationIsWithdrawnByClear(): void
    {
        $this->failTimes(4, 'quinn', '198.51.100.9');
        self::assertSame(0, $this->throttle->reserve('quinn', '198.51.100.9'));

        $this->throttle->clear('quinn', '198.51.100.9');

        self::assertSame(0, $this->throttle->secondsUntilAllowed('quinn', '198.51.100.9'));
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM login_attempts'));
    }

    public function testClearAndPrune(): void
    {
        $this->failTimes(5, 'nia', '198.51.100.9');
        $this->throttle->clear('nia', '198.51.100.9');
        self::assertSame(0, $this->throttle->secondsUntilAllowed('nia', '198.51.100.9'));

        $this->failTimes(2, 'oz', '198.51.100.9');
        $this->clock->advance('+16 minutes');
        self::assertSame(2, $this->throttle->prune());
    }

    private function failTimes(int $times, string $scope, string $ip): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $this->throttle->recordFailure($scope, $ip);
        }
    }
}

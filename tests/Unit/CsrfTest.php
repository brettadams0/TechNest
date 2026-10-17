<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Security\ArraySession;
use TechNest\Security\Csrf;

final class CsrfTest extends TestCase
{
    public function testTokenIsStableForASessionAndValidates(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->isValid($token));
    }

    public function testRejectsMissingAndWrongTokens(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertFalse($csrf->isValid(''));
        self::assertFalse($csrf->isValid(strrev($token)));
        self::assertFalse($csrf->isValid(substr($token, 0, 32)));
    }

    public function testNothingValidatesBeforeATokenWasIssued(): void
    {
        self::assertFalse((new Csrf(new ArraySession()))->isValid(''));
    }

    public function testRotationInvalidatesTheOldToken(): void
    {
        $csrf = new Csrf(new ArraySession());
        $old = $csrf->token();
        $csrf->rotate();

        self::assertFalse($csrf->isValid($old));
        self::assertNotSame($old, $csrf->token());
    }

    public function testSessionsDoNotShareTokens(): void
    {
        self::assertNotSame((new Csrf(new ArraySession()))->token(), (new Csrf(new ArraySession()))->token());
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Support\Validator;

final class ValidatorTest extends TestCase
{
    public function testStringsAreTrimmedAndLengthChecked(): void
    {
        $v = new Validator(['name' => '  Al  ', 'bio' => str_repeat('x', 11)]);

        self::assertSame('Al', $v->string('name', 'Name', 3));
        $v->string('bio', 'Bio', 0, 10);

        self::assertSame(['name' => 'Name must be at least 3 characters.', 'bio' => 'Bio must be at most 10 characters.'], $v->errors());
    }

    public function testPatternMessages(): void
    {
        $v = new Validator(['username' => 'bad name!']);
        $v->string('username', 'Username', 3, 30, '/^[a-z]+$/', 'Letters only.');

        self::assertSame(['username' => 'Letters only.'], $v->errors());
    }

    public function testEmailIsNormalisedToLowercase(): void
    {
        $v = new Validator(['email' => ' Brett@Example.COM ', 'bad' => 'nope']);

        self::assertSame('brett@example.com', $v->email('email'));
        $v->email('bad');
        self::assertArrayHasKey('bad', $v->errors());
        self::assertArrayNotHasKey('email', $v->errors());
    }

    public function testPasswordsAreNeverTrimmedAndRespectBcryptsLimit(): void
    {
        $v = new Validator(['p1' => '  ten chars  ', 'p2' => str_repeat('a', 73)]);

        self::assertSame('  ten chars  ', $v->password('p1'));
        $v->password('p2');
        self::assertSame(['p2' => 'Password must be at most 72 characters.'], $v->errors());
    }

    public function testMatches(): void
    {
        $v = new Validator(['a' => 'one', 'b' => 'two']);
        $v->matches('b', 'a', 'Must match.');

        self::assertSame(['b' => 'Must match.'], $v->errors());
    }

    public function testIntegersMoneyAndChoices(): void
    {
        $v = new Validator(['stock' => '12', 'neg' => '-1', 'price' => '19.99', 'badPrice' => '19.999', 'province' => 'ON', 'nope' => 'XX']);

        self::assertSame(12, $v->int('stock', 'Stock', 0, 100));
        $v->int('neg', 'Neg', 0, 100);
        self::assertSame(1999, $v->money('price', 'Price'));
        $v->money('badPrice', 'Price');
        self::assertSame('ON', $v->oneOf('province', 'province', ['ON' => 'Ontario']));
        $v->oneOf('nope', 'province', ['ON' => 'Ontario']);

        self::assertSame(['neg', 'badPrice', 'nope'], array_keys($v->errors()));
    }

    public function testFirstErrorPerFieldWins(): void
    {
        $v = new Validator([]);
        $v->addError('x', 'first');
        $v->addError('x', 'second');

        self::assertSame(['x' => 'first'], $v->errors());
    }

    public function testNonScalarInputIsTreatedAsEmpty(): void
    {
        $v = new Validator(['name' => ['array', 'injection']]);

        self::assertSame('', $v->string('name', 'Name'));
        self::assertTrue($v->fails());
    }
}

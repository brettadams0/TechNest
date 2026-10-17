<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Support\Money;

final class MoneyTest extends TestCase
{
    #[DataProvider('formatted')]
    public function testFormatsCents(int $cents, string $expected): void
    {
        self::assertSame($expected, Money::format($cents));
    }

    /** @return iterable<string, array{int, string}> */
    public static function formatted(): iterable
    {
        yield 'zero' => [0, '$0.00'];
        yield 'under a dollar' => [5, '$0.05'];
        yield 'thousands separator' => [119999, '$1,199.99'];
        yield 'negative' => [-1250, '-$12.50'];
    }

    #[DataProvider('parsable')]
    public function testParsesTypedAmountsWithoutFloats(string $input, int $expected): void
    {
        self::assertSame($expected, Money::parse($input));
    }

    /** @return iterable<string, array{string, int}> */
    public static function parsable(): iterable
    {
        yield 'plain' => ['1199.99', 119999];
        yield 'with separators and symbol' => ['$1,199.99', 119999];
        yield 'one decimal place' => ['0.5', 50];
        yield 'whole dollars' => ['15', 1500];
        yield 'a classic float trap' => ['0.29', 29];
        yield 'surrounding whitespace' => ['  42.10 ', 4210];
    }

    #[DataProvider('unparsable')]
    public function testRejectsAmountsItCannotRepresentExactly(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::parse($input);
    }

    /** @return iterable<string, array{string}> */
    public static function unparsable(): iterable
    {
        yield 'empty' => [''];
        yield 'negative' => ['-5'];
        yield 'three decimal places' => ['1.999'];
        yield 'words' => ['ten dollars'];
        yield 'scientific notation' => ['1e3'];
    }

    public function testShortFormatDropsZeroCentsOnly(): void
    {
        self::assertSame('$100', Money::formatShort(10000));
        self::assertSame('$99.50', Money::formatShort(9950));
    }

    public function testDecimalRoundTripsThroughParse(): void
    {
        foreach ([0, 7, 1999, 119999] as $cents) {
            self::assertSame($cents, Money::parse(Money::toDecimal($cents)));
        }
    }
}

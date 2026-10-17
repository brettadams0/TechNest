<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Checkout\ShippingAddress;
use TechNest\Database\Migrator;

final class ShippingAddressTest extends TestCase
{
    #[DataProvider('postalCodes')]
    public function testNormalisesCanadianPostalCodes(string $input, ?string $expected): void
    {
        self::assertSame($expected, ShippingAddress::normalisePostalCode($input));
    }

    /** @return iterable<array{string, ?string}> */
    public static function postalCodes(): iterable
    {
        yield ['N9B 3P4', 'N9B 3P4'];
        yield ['n9b3p4', 'N9B 3P4'];
        yield ['  m5v 2t6 ', 'M5V 2T6'];
        yield ['D9B 3P4', null]; // no D as a first letter
        yield ['N9B 3P', null];
        yield ['12345', null];
    }

    public function testMigrationFilesSplitIntoStatements(): void
    {
        $sql = "-- comment\nCREATE TABLE a (x INT);\n\nCREATE TABLE b (\n  y INT\n);\n";

        self::assertSame(['CREATE TABLE a (x INT)', "CREATE TABLE b (\n  y INT\n)"], Migrator::statements($sql));
    }
}

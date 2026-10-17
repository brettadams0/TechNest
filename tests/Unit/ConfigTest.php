<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use TechNest\Config;

final class ConfigTest extends TestCase
{
    public function testReadsDotEnvFiles(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        self::assertIsString($file);
        file_put_contents($file, "# comment\nDB_DRIVER=mysql\nDB_NAME=\"technest\"\nDB_PASS='p=ss word'\n\nNOT A PAIR\n");

        try {
            self::assertSame(
                ['DB_DRIVER' => 'mysql', 'DB_NAME' => 'technest', 'DB_PASS' => 'p=ss word'],
                Config::readDotEnv($file),
            );
        } finally {
            unlink($file);
        }
    }

    public function testDefaultsToSqliteAndLocal(): void
    {
        $config = Config::fromArray([]);

        self::assertSame('sqlite', $config->dbDriver());
        self::assertSame('local', $config->env());
        self::assertTrue($config->debug());
    }

    public function testProductionTurnsDebugOffUnlessAskedFor(): void
    {
        self::assertFalse(Config::fromArray(['APP_ENV' => 'production'])->debug());
        self::assertTrue(Config::fromArray(['APP_ENV' => 'production', 'APP_DEBUG' => 'true'])->debug());
    }

    public function testMysqlNamesEveryMissingCredential(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('set DB_NAME and DB_USER');
        Config::fromArray(['DB_DRIVER' => 'mysql'])->requireMysqlCredentials();
    }

    public function testTimezoneDefaultsToTorontoAndRejectsNonsense(): void
    {
        self::assertSame('America/Toronto', Config::fromArray([])->timezone()->getName());
        self::assertSame('Europe/London', Config::fromArray(['APP_TIMEZONE' => 'Europe/London'])->timezone()->getName());

        $this->expectException(RuntimeException::class);
        Config::fromArray(['APP_TIMEZONE' => 'Mars/Olympus'])->timezone();
    }

    public function testRejectsUnknownDrivers(): void
    {
        $this->expectException(RuntimeException::class);
        Config::fromArray(['DB_DRIVER' => 'postgres'])->dbDriver();
    }
}

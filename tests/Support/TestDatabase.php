<?php

declare(strict_types=1);

namespace TechNest\Tests\Support;

use TechNest\Config;
use TechNest\Database\Database;
use TechNest\Database\Migrator;
use TechNest\Support\Clock;

/**
 * A clean, migrated database for each test.
 *
 * SQLite (the default) gets a brand-new in-memory database per test. With
 * TEST_DB_DRIVER=mysql the suite runs against a real MySQL server instead:
 * the schema is built once per process and every table is truncated before
 * each test. CI runs both.
 */
final class TestDatabase
{
    private static ?Database $mysql = null;

    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function create(Clock $clock): Database
    {
        if (self::driver() === 'mysql') {
            return self::mysql($clock);
        }

        $db = Database::sqlite(':memory:');
        (new Migrator($db, self::root() . '/database/migrations', $clock))->migrate();

        return $db;
    }

    public static function driver(): string
    {
        return getenv('TEST_DB_DRIVER') === 'mysql' ? 'mysql' : 'sqlite';
    }

    private static function mysql(Clock $clock): Database
    {
        if (self::$mysql === null) {
            $config = Config::fromArray([
                'DB_DRIVER' => 'mysql',
                'DB_HOST' => self::env('TEST_DB_HOST', '127.0.0.1'),
                'DB_PORT' => self::env('TEST_DB_PORT', '3306'),
                'DB_NAME' => self::env('TEST_DB_NAME', 'technest_test'),
                'DB_USER' => self::env('TEST_DB_USER', 'root'),
                'DB_PASS' => self::env('TEST_DB_PASS', ''),
            ]);
            self::$mysql = Database::connect($config, self::root());
            $migrator = new Migrator(self::$mysql, self::root() . '/database/migrations', $clock);
            $migrator->dropAll();
            $migrator->migrate();

            return self::$mysql;
        }

        $pdo = self::$mysql->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['order_status_history', 'order_items', 'orders', 'product_options', 'products', 'categories', 'login_attempts', 'admins', 'users', 'themes'] as $table) {
            $pdo->exec("TRUNCATE TABLE {$table}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        return self::$mysql;
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}

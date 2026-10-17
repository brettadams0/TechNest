<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TechNest\Database\Database;
use TechNest\Tests\Support\TestDatabase;

/**
 * Transactions against a real SQLite file shared by two connections, the way
 * two Apache workers share it. The in-memory databases the rest of the suite
 * uses are private to one connection, so they cannot show locking.
 */
final class SqliteTransactionTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        if (TestDatabase::driver() !== 'sqlite') {
            self::markTestSkipped('SQLite locking only.');
        }

        $this->path = sys_get_temp_dir() . '/technest-tx-' . bin2hex(random_bytes(6)) . '.sqlite';
        $db = Database::sqlite($this->path);
        $db->execute('CREATE TABLE counters (id INTEGER PRIMARY KEY, n INTEGER NOT NULL)');
        $db->execute('INSERT INTO counters (id, n) VALUES (1, 0), (2, 0)');
    }

    protected function tearDown(): void
    {
        if (isset($this->path)) {
            foreach (['', '-wal', '-shm'] as $suffix) {
                if (is_file($this->path . $suffix)) {
                    unlink($this->path . $suffix);
                }
            }
        }
    }

    public function testATransactionTakesTheWriteLockBeforeItReads(): void
    {
        // Regression: a deferred transaction that had read could not upgrade
        // to a writer while another connection wrote, and SQLite failed it
        // at once ("database is locked") instead of waiting. Now the lock is
        // taken first, so it is the other writer that waits.
        $a = Database::sqlite($this->path);
        $b = Database::sqlite($this->path);
        $b->pdo()->exec('PRAGMA busy_timeout = 0');

        $a->transaction(static function (Database $a) use ($b): void {
            $a->fetchInt('SELECT n FROM counters WHERE id = 1');

            try {
                $b->execute('UPDATE counters SET n = 1 WHERE id = 2');
                self::fail('Another connection wrote in the middle of the transaction');
            } catch (PDOException $e) {
                self::assertStringContainsString('locked', $e->getMessage());
            }

            $a->execute('UPDATE counters SET n = n + 1 WHERE id = 1');
        });

        $b->execute('UPDATE counters SET n = 1 WHERE id = 2');
        self::assertSame(2, $b->fetchInt('SELECT SUM(n) FROM counters'));
    }

    public function testNestedCallsJoinTheOuterTransactionAndRollBackWithIt(): void
    {
        $db = Database::sqlite($this->path);

        try {
            $db->transaction(static function (Database $db): void {
                $db->execute('UPDATE counters SET n = 5 WHERE id = 1');
                $db->transaction(static fn(Database $db): int => $db->execute('UPDATE counters SET n = 5 WHERE id = 2'));

                throw new RuntimeException('Fail after the nested call returned');
            });
        } catch (RuntimeException) {
        }

        self::assertSame(0, $db->fetchInt('SELECT SUM(n) FROM counters'));

        // The connection is left usable.
        $db->transaction(static fn(Database $db): int => $db->execute('UPDATE counters SET n = 1 WHERE id = 1'));
        self::assertSame(1, $db->fetchInt('SELECT SUM(n) FROM counters'));
    }
}

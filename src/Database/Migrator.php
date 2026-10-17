<?php

declare(strict_types=1);

namespace TechNest\Database;

use RuntimeException;
use TechNest\Support\Clock;

/**
 * Applies numbered SQL files from database/migrations/{driver}/ in order and
 * records each one in a `migrations` table, so running it twice is a no-op.
 *
 * There is one directory per driver because the DDL genuinely differs
 * (AUTO_INCREMENT vs AUTOINCREMENT, DATETIME vs TEXT); the queries the
 * application runs afterwards are written to be portable across both.
 *
 * Note that MySQL commits DDL implicitly, so a migration that fails halfway
 * on MySQL is not rolled back. Keep each file small enough to fix by hand.
 */
final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly string $migrationsDir,
        private readonly Clock $clock,
    ) {}

    /** @return list<string> names of the migrations applied by this call */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $applied = array_column(
            $this->db->fetchAll('SELECT name FROM migrations'),
            'name',
        );

        $ran = [];
        foreach ($this->pending($applied) as $name => $path) {
            foreach (self::statements((string) file_get_contents($path)) as $statement) {
                $this->db->pdo()->exec($statement);
            }
            $this->db->execute(
                'INSERT INTO migrations (name, applied_at) VALUES (?, ?)',
                [$name, $this->clock->now()->format('Y-m-d H:i:s')],
            );
            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Drop every application table. Used by `db:reset` and the test suite.
     */
    public function dropAll(): void
    {
        $tables = [
            'order_status_history', 'order_items', 'orders', 'product_options',
            'products', 'categories', 'login_attempts', 'admins', 'users',
            'themes', 'migrations',
        ];

        $mysql = $this->db->driver() === 'mysql';
        if ($mysql) {
            $this->db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
        }
        foreach ($tables as $table) {
            $this->db->pdo()->exec("DROP TABLE IF EXISTS {$table}");
        }
        if ($mysql) {
            $this->db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Split a migration file into statements. The schema files contain no
     * semicolons inside string literals, so a statement ends at a semicolon
     * that closes a line.
     *
     * @return list<string>
     */
    public static function statements(string $sql): array
    {
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        $parts = preg_split('/;\s*(?:\R|$)/', $sql) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn(string $s): bool => $s !== ''));
    }

    /**
     * @param list<mixed> $applied
     *
     * @return array<string, string> name => path, in order
     */
    private function pending(array $applied): array
    {
        $dir = $this->migrationsDir . '/' . $this->db->driver();
        $files = glob($dir . '/*.sql');
        if ($files === false || $files === []) {
            throw new RuntimeException("No migrations found in {$dir}");
        }
        sort($files);

        $pending = [];
        foreach ($files as $path) {
            $name = basename($path, '.sql');
            if (!in_array($name, $applied, true)) {
                $pending[$name] = $path;
            }
        }

        return $pending;
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(190) PRIMARY KEY, applied_at VARCHAR(19) NOT NULL)',
        );
    }
}

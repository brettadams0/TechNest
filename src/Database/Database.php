<?php

declare(strict_types=1);

namespace TechNest\Database;

use PDO;
use PDOStatement;
use TechNest\Config;
use Throwable;

/**
 * A thin layer over PDO: every query is a prepared statement, parameters are
 * bound with their real types (so LIMIT ? works on MySQL), and transactions
 * roll back on any exception.
 *
 * @phpstan-type DbRow array<string, mixed>
 */
final class Database
{
    public function __construct(private readonly PDO $pdo) {}

    public static function connect(Config $config, string $rootDir): self
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if ($config->dbDriver() === 'mysql') {
            $config->requireMysqlCredentials();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config->get('DB_HOST', '127.0.0.1'),
                $config->get('DB_PORT', '3306'),
                $config->get('DB_NAME'),
            );
            $pdo = new PDO($dsn, $config->get('DB_USER'), $config->get('DB_PASS'), $options);
            $pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,ONLY_FULL_GROUP_BY'");

            return new self($pdo);
        }

        $path = $config->get('DB_PATH', $rootDir . '/var/technest.sqlite');

        return self::sqlite($path);
    }

    public static function sqlite(string $path = ':memory:'): self
    {
        if ($path !== ':memory:' && !is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        if ($path !== ':memory:') {
            $pdo->exec('PRAGMA journal_mode = WAL');
        }

        return new self($pdo);
    }

    public function driver(): string
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        return is_string($driver) ? $driver : 'unknown';
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return list<DbRow>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        /** @var list<DbRow> */
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return DbRow|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        /** @var DbRow|false $row */
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** @param array<int|string, mixed> $params */
    public function fetchInt(string $sql, array $params = []): int
    {
        $value = $this->fetchValue($sql, $params);

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Run a write and return the number of affected rows. Conditional updates
     * ("... WHERE stock >= ?") rely on this to detect a lost race.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /** @param array<int|string, mixed> $params */
    public function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Run $work inside a transaction, committing on return and rolling back on
     * any exception. Nested calls join the outer transaction.
     *
     * @template T
     *
     * @param callable(self): T $work
     *
     * @return T
     */
    public function transaction(callable $work): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $work($this);
        }

        $this->pdo->beginTransaction();
        try {
            $result = $work($this);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<int|string, mixed> $params */
    private function run(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : ':' . ltrim($key, ':');
            $statement->bindValue($name, $value, match (true) {
                is_int($value), is_bool($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            });
        }

        $statement->execute();

        return $statement;
    }
}

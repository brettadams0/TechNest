<?php

declare(strict_types=1);

namespace TechNest\User;

use TechNest\Database\Database;
use TechNest\Database\Row;
use TechNest\Support\Clock;

final class UserRepository
{
    private const COLUMNS = 'id, username, email, is_active, created_at, session_version';

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

    public function find(int $id): ?User
    {
        $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ?', [$id]);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByUsername(string $username): ?User
    {
        $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE username = ?', [$username]);

        return $row === null ? null : User::fromRow($row);
    }

    /**
     * Look a user up by username or email for login, returning the password
     * hash alongside. The hash never leaves the auth layer.
     *
     * @return array{0: User, 1: string}|null
     */
    public function findForLogin(string $login): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT ' . self::COLUMNS . ', password_hash FROM users WHERE username = ? OR email = ?',
            [$login, $login],
        );

        return $row === null ? null : [User::fromRow($row), Row::string($row, 'password_hash')];
    }

    public function passwordHash(int $id): ?string
    {
        $hash = $this->db->fetchValue('SELECT password_hash FROM users WHERE id = ?', [$id]);

        return is_string($hash) ? $hash : null;
    }

    /** @return array{username: bool, email: bool} which identifiers are taken */
    public function taken(string $username, string $email): array
    {
        return [
            'username' => $this->db->fetchInt('SELECT COUNT(*) FROM users WHERE username = ?', [$username]) > 0,
            'email' => $this->db->fetchInt('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0,
        ];
    }

    public function create(string $username, string $email, string $passwordHash): User
    {
        $id = $this->db->insert(
            'INSERT INTO users (username, email, password_hash, is_active, created_at) VALUES (?, ?, ?, 1, ?)',
            [$username, $email, $passwordHash, $this->now()],
        );

        return $this->find($id) ?? throw new \LogicException('User vanished after insert');
    }

    /** Store a re-hash of the same password (e.g. after a cost change). */
    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->db->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    /**
     * Set a new password and bump the session version, which signs out every
     * other session of the account on its next request.
     */
    public function changePassword(int $id, string $hash): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?',
            [$hash, $id],
        );
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->execute('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public function isAdmin(int $id): bool
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM admins WHERE user_id = ?', [$id]) > 0;
    }

    public function grantAdmin(int $id, ?int $grantedBy): void
    {
        if ($this->isAdmin($id)) {
            return;
        }
        $this->db->execute(
            'INSERT INTO admins (user_id, granted_at, granted_by) VALUES (?, ?, ?)',
            [$id, $this->now(), $grantedBy],
        );
    }

    public function revokeAdmin(int $id): void
    {
        $this->db->execute('DELETE FROM admins WHERE user_id = ?', [$id]);
    }

    /**
     * Every account with its order count, lifetime spend (excluding
     * cancelled orders) and admin flag, for the admin users table.
     *
     * @return list<array{user: User, is_admin: bool, orders: int, spent_cents: int}>
     */
    public function listWithStats(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, u.is_active, u.created_at,
                    CASE WHEN a.user_id IS NULL THEN 0 ELSE 1 END AS is_admin,
                    COUNT(o.id) AS orders,
                    COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total_cents ELSE 0 END), 0) AS spent_cents
               FROM users u
               LEFT JOIN admins a ON a.user_id = u.id
               LEFT JOIN orders o ON o.user_id = u.id
              GROUP BY u.id, u.username, u.email, u.is_active, u.created_at, a.user_id
              ORDER BY u.id",
        );

        return array_map(static fn(array $row): array => [
            'user' => User::fromRow($row),
            'is_admin' => Row::bool($row, 'is_admin'),
            'orders' => Row::int($row, 'orders'),
            'spent_cents' => Row::int($row, 'spent_cents'),
        ], $rows);
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Security;

use TechNest\Database\Database;
use TechNest\Support\Clock;

/**
 * Slows down password guessing: after MAX_FAILURES failed logins for one
 * username from one IP within WINDOW_MINUTES, further attempts are refused
 * until the oldest failure ages out of the window.
 *
 * Keyed on username+IP rather than username alone so an attacker cannot lock
 * a real user out of their account from somewhere else. Stored in the
 * database, not the session, because an attacker simply discards cookies.
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const WINDOW_MINUTES = 15;

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

    /** Seconds until the next attempt is allowed; 0 when not locked. */
    public function secondsUntilAllowed(string $username, string $ip): int
    {
        $windowStart = $this->clock->now()->modify('-' . self::WINDOW_MINUTES . ' minutes');

        $recent = $this->db->fetchAll(
            'SELECT attempted_at FROM login_attempts
              WHERE throttle_key = ? AND attempted_at > ?
              ORDER BY attempted_at DESC',
            [self::key($username, $ip), $windowStart->format('Y-m-d H:i:s')],
        );

        if (count($recent) < self::MAX_FAILURES) {
            return 0;
        }

        // The attempt that must expire before another is allowed.
        $blocking = $recent[self::MAX_FAILURES - 1]['attempted_at'];
        $unlocksAt = strtotime((string) (is_scalar($blocking) ? $blocking : '') . ' UTC') + self::WINDOW_MINUTES * 60;

        return max(1, $unlocksAt - $this->clock->now()->getTimestamp());
    }

    public function recordFailure(string $username, string $ip): void
    {
        $this->db->execute(
            'INSERT INTO login_attempts (throttle_key, attempted_at) VALUES (?, ?)',
            [self::key($username, $ip), $this->clock->now()->format('Y-m-d H:i:s')],
        );
    }

    public function clear(string $username, string $ip): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE throttle_key = ?', [self::key($username, $ip)]);
    }

    /** Housekeeping: attempts older than the window can never matter again. */
    public function prune(): int
    {
        $cutoff = $this->clock->now()->modify('-' . self::WINDOW_MINUTES . ' minutes');

        return $this->db->execute('DELETE FROM login_attempts WHERE attempted_at <= ?', [$cutoff->format('Y-m-d H:i:s')]);
    }

    private static function key(string $username, string $ip): string
    {
        return hash('sha256', strtolower(trim($username)) . '|' . $ip);
    }
}

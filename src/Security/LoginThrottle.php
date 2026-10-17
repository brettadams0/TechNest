<?php

declare(strict_types=1);

namespace TechNest\Security;

use TechNest\Database\Database;
use TechNest\Support\Clock;

/**
 * Slows down password guessing: after MAX_FAILURES failed attempts against
 * one scope from one IP within WINDOW_MINUTES, further attempts are refused
 * until the oldest failure ages out of the window.
 *
 * The scope is the *account* when the login resolved to one, not the text
 * that was typed. MySQL's accent- and width-insensitive collation lets
 * "admin", "ádmin" and "ＡDMIN" all reach the same row, and the username and
 * email of one account are two spellings too; keying on what was typed gave
 * each spelling its own five guesses.
 *
 * Each attempt is reserved (inserted) *before* the password is checked and
 * counted afterwards, so a burst of concurrent requests cannot all pass the
 * check while the first ones are still inside bcrypt.
 *
 * Keyed with the IP so an attacker cannot lock a real user out from somewhere
 * else, and stored in the database rather than the session because an
 * attacker simply discards cookies.
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const WINDOW_MINUTES = 15;

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

    /** The scope for an attempt that resolved to a known account. */
    public static function accountScope(int $userId): string
    {
        return 'user:' . $userId;
    }

    /** The scope for an attempt that matched no account. */
    public static function inputScope(string $login): string
    {
        return 'login:' . strtolower(trim($login));
    }

    /**
     * Claim one attempt. Returns 0 when the caller may go ahead (the reserved
     * row then stands as the failure record unless clear() is called on
     * success), or the seconds to wait when the scope is locked (the
     * reservation is withdrawn, so refused attempts do not extend the lock).
     */
    public function reserve(string $scope, string $ip): int
    {
        $key = self::key($scope, $ip);
        $id = $this->db->insert(
            'INSERT INTO login_attempts (throttle_key, attempted_at) VALUES (?, ?)',
            [$key, $this->now()],
        );
        $this->maybePrune();

        $wait = $this->wait($key, self::MAX_FAILURES + 1);
        if ($wait > 0) {
            $this->db->execute('DELETE FROM login_attempts WHERE id = ?', [$id]);
        }

        return $wait;
    }

    /** Seconds until the next attempt would be allowed; 0 when not locked. */
    public function secondsUntilAllowed(string $scope, string $ip): int
    {
        return $this->wait(self::key($scope, $ip), self::MAX_FAILURES);
    }

    /** Record a failure without a prior reservation (e.g. a probe that is not a login). */
    public function recordFailure(string $scope, string $ip): void
    {
        $this->db->execute(
            'INSERT INTO login_attempts (throttle_key, attempted_at) VALUES (?, ?)',
            [self::key($scope, $ip), $this->now()],
        );
        $this->maybePrune();
    }

    public function clear(string $scope, string $ip): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE throttle_key = ?', [self::key($scope, $ip)]);
    }

    /** Housekeeping: attempts older than the window can never matter again. */
    public function prune(): int
    {
        $cutoff = $this->clock->now()->modify('-' . self::WINDOW_MINUTES . ' minutes');

        return $this->db->execute('DELETE FROM login_attempts WHERE attempted_at <= ?', [$cutoff->format('Y-m-d H:i:s')]);
    }

    /**
     * Seconds until fewer than $limit attempts remain in the window, or 0 if
     * there already are.
     */
    private function wait(string $key, int $limit): int
    {
        $windowStart = $this->clock->now()->modify('-' . self::WINDOW_MINUTES . ' minutes');

        $recent = $this->db->fetchAll(
            'SELECT attempted_at FROM login_attempts
              WHERE throttle_key = ? AND attempted_at > ?
              ORDER BY attempted_at DESC, id DESC',
            [$key, $windowStart->format('Y-m-d H:i:s')],
        );

        if (count($recent) < $limit) {
            return 0;
        }

        // The attempt that must age out before the count drops below $limit.
        $blocking = $recent[$limit - 1]['attempted_at'];
        $unlocksAt = strtotime((string) (is_scalar($blocking) ? $blocking : '') . ' UTC') + self::WINDOW_MINUTES * 60;

        return max(1, $unlocksAt - $this->clock->now()->getTimestamp());
    }

    /**
     * Failed logins for usernames that do not exist add rows nothing else
     * deletes, so roughly one write in a hundred also sweeps expired rows.
     */
    private function maybePrune(): void
    {
        if (random_int(1, 100) === 1) {
            $this->prune();
        }
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }

    private static function key(string $scope, string $ip): string
    {
        return hash('sha256', $scope . '|' . $ip);
    }
}

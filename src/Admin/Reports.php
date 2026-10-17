<?php

declare(strict_types=1);

namespace TechNest\Admin;

use DateTimeImmutable;
use DateTimeZone;
use TechNest\Database\Database;
use TechNest\Database\Row;
use TechNest\Support\Clock;

/**
 * The numbers on the admin dashboard. Every query excludes cancelled orders.
 *
 * Revenue is goods sold: order subtotals and line totals, before shipping
 * and HST. Tax is collected for the government, and counting it in one
 * figure but not another would leave the categories never adding up to the
 * headline.
 *
 * Days are the store's days. Timestamps are stored in UTC, but the rest of
 * the UI shows dates in the store's timezone, so window boundaries are local
 * midnights converted to UTC, and orders are bucketed into days in PHP. That
 * keeps the SQL identical on SQLite and MySQL, neither of which is told
 * about timezones.
 */
final class Reports
{
    private readonly DateTimeZone $utc;

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
        $this->utc = new DateTimeZone('UTC');
    }

    /**
     * Headline figures for the last $days days, with the same figures for the
     * period before it so the dashboard can show the change.
     *
     * @return array{current: array{revenue: int, orders: int, units: int, customers: int, aov: int}, previous: array{revenue: int, orders: int, units: int, customers: int, aov: int}}
     */
    public function summary(int $days = 30): array
    {
        $end = $this->clock->now();
        $start = $this->periodStart($days);
        // The current period ends now, part way through today, so the one it
        // is compared with is exactly as long rather than $days whole days.
        $length = $end->getTimestamp() - $start->getTimestamp();
        $previousStart = $start->modify("-{$length} seconds");

        return [
            'current' => $this->figures($start, $end),
            'previous' => $this->figures($previousStart, $start),
        ];
    }

    /**
     * Revenue and order count per day, oldest first, with zero-filled gaps so
     * the chart's x-axis is continuous.
     *
     * @return list<array{date: string, revenue: int, orders: int}>
     */
    public function dailyRevenue(int $days = 30): array
    {
        $start = $this->periodStart($days);
        $rows = $this->db->fetchAll(
            "SELECT created_at, subtotal_cents
               FROM orders
              WHERE status <> 'cancelled' AND created_at >= ?",
            [$this->utcString($start)],
        );

        $byDay = [];
        foreach ($rows as $row) {
            $day = $this->localDate(Row::string($row, 'created_at'));
            $byDay[$day]['revenue'] = ($byDay[$day]['revenue'] ?? 0) + Row::int($row, 'subtotal_cents');
            $byDay[$day]['orders'] = ($byDay[$day]['orders'] ?? 0) + 1;
        }

        $series = [];
        for ($day = $start; count($series) < $days; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'revenue' => $byDay[$key]['revenue'] ?? 0,
                'orders' => $byDay[$key]['orders'] ?? 0,
            ];
        }

        return $series;
    }

    /** @return list<array{name: string, revenue: int, units: int}> */
    public function revenueByCategory(int $days = 30): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.name, SUM(i.line_total_cents) AS revenue, SUM(i.quantity) AS units
               FROM order_items i
               JOIN orders o ON o.id = i.order_id
               JOIN products p ON p.id = i.product_id
               JOIN categories c ON c.id = p.category_id
              WHERE o.status <> 'cancelled' AND o.created_at >= ?
              GROUP BY c.id, c.name
              ORDER BY revenue DESC",
            [$this->utcString($this->periodStart($days))],
        );

        return array_map(static fn(array $r): array => [
            'name' => Row::string($r, 'name'),
            'revenue' => Row::int($r, 'revenue'),
            'units' => Row::int($r, 'units'),
        ], $rows);
    }

    /** @return list<array{name: string, units: int, revenue: int}> */
    public function topProducts(int $days = 30, int $limit = 5): array
    {
        $rows = $this->db->fetchAll(
            "SELECT p.name, SUM(i.quantity) AS units, SUM(i.line_total_cents) AS revenue
               FROM order_items i
               JOIN orders o ON o.id = i.order_id
               JOIN products p ON p.id = i.product_id
              WHERE o.status <> 'cancelled' AND o.created_at >= ?
              GROUP BY p.id, p.name
              ORDER BY units DESC, revenue DESC
              LIMIT ?",
            [$this->utcString($this->periodStart($days)), $limit],
        );

        return array_map(static fn(array $r): array => [
            'name' => Row::string($r, 'name'),
            'units' => Row::int($r, 'units'),
            'revenue' => Row::int($r, 'revenue'),
        ], $rows);
    }

    /** Local midnight at the start of a window of $days days ending today. */
    private function periodStart(int $days): DateTimeImmutable
    {
        return $this->clock->now()->setTimezone($this->timezone)->setTime(0, 0)->modify('-' . ($days - 1) . ' days');
    }

    /** A moment as stored in the database: a UTC 'Y-m-d H:i:s' string. */
    private function utcString(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone($this->utc)->format('Y-m-d H:i:s');
    }

    /** The store's calendar date for a stored UTC timestamp. */
    private function localDate(string $utc): string
    {
        return (new DateTimeImmutable($utc, $this->utc))->setTimezone($this->timezone)->format('Y-m-d');
    }

    /** @return array{revenue: int, orders: int, units: int, customers: int, aov: int} */
    private function figures(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $range = [$this->utcString($from), $this->utcString($to)];

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS orders, COALESCE(SUM(subtotal_cents), 0) AS revenue, COUNT(DISTINCT user_id) AS customers
               FROM orders
              WHERE status <> 'cancelled' AND created_at >= ? AND created_at < ?",
            $range,
        ) ?? ['orders' => 0, 'revenue' => 0, 'customers' => 0];

        $units = $this->db->fetchInt(
            "SELECT COALESCE(SUM(i.quantity), 0)
               FROM order_items i
               JOIN orders o ON o.id = i.order_id
              WHERE o.status <> 'cancelled' AND o.created_at >= ? AND o.created_at < ?",
            $range,
        );

        $orders = Row::int($row, 'orders');
        $revenue = Row::int($row, 'revenue');

        return [
            'revenue' => $revenue,
            'orders' => $orders,
            'units' => $units,
            'customers' => Row::int($row, 'customers'),
            'aov' => $orders === 0 ? 0 : intdiv($revenue, $orders),
        ];
    }
}

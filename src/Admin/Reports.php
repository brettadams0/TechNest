<?php

declare(strict_types=1);

namespace TechNest\Admin;

use DateTimeImmutable;
use TechNest\Database\Database;
use TechNest\Database\Row;
use TechNest\Support\Clock;

/**
 * The numbers on the admin dashboard. Every query excludes cancelled orders
 * from revenue and works unchanged on SQLite and MySQL (DATE() exists in
 * both, and timestamps are stored as UTC 'Y-m-d H:i:s' strings).
 */
final class Reports
{
    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

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
        $previousStart = $start->modify("-{$days} days");

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
            "SELECT DATE(created_at) AS day, SUM(total_cents) AS revenue, COUNT(*) AS orders
               FROM orders
              WHERE status <> 'cancelled' AND created_at >= ?
              GROUP BY DATE(created_at)",
            [$start->format('Y-m-d H:i:s')],
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[Row::string($row, 'day')] = ['revenue' => Row::int($row, 'revenue'), 'orders' => Row::int($row, 'orders')];
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
            [$this->periodStart($days)->format('Y-m-d H:i:s')],
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
            [$this->periodStart($days)->format('Y-m-d H:i:s'), $limit],
        );

        return array_map(static fn(array $r): array => [
            'name' => Row::string($r, 'name'),
            'units' => Row::int($r, 'units'),
            'revenue' => Row::int($r, 'revenue'),
        ], $rows);
    }

    /** Midnight UTC at the start of a window of $days days ending today. */
    private function periodStart(int $days): DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0)->modify('-' . ($days - 1) . ' days');
    }

    /** @return array{revenue: int, orders: int, units: int, customers: int, aov: int} */
    private function figures(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $range = [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')];

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS revenue, COUNT(DISTINCT user_id) AS customers
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

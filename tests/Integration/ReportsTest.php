<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use DateTimeZone;
use TechNest\Admin\Reports;
use TechNest\Tests\Support\DatabaseTestCase;

final class ReportsTest extends DatabaseTestCase
{
    private Reports $reports;
    private int $alice;
    private int $speaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reports = new Reports($this->db, $this->clock);

        $alice = $this->fixtures->user('alice');
        $bob = $this->fixtures->user('bob');
        $speaker = $this->fixtures->product(['name' => 'Speaker', 'category' => 'audio', 'price' => 10000]);
        $this->alice = $alice->id;
        $this->speaker = $speaker->id;
        $phone = $this->fixtures->product(['name' => 'Phone', 'category' => 'phones', 'price' => 50000]);

        // "Now" is 2026-03-10 15:00 UTC.
        $this->order($alice->id, '2026-03-10 09:00:00', 'delivered', [[$speaker->id, 'Speaker', 10000, 2]]);
        $this->order($bob->id, '2026-03-08 12:00:00', 'shipped', [[$phone->id, 'Phone', 50000, 1]]);
        $this->order($bob->id, '2026-03-08 13:00:00', 'cancelled', [[$phone->id, 'Phone', 50000, 3]]);
        $this->order($alice->id, '2026-02-01 12:00:00', 'delivered', [[$speaker->id, 'Speaker', 10000, 1]]);
    }

    public function testSummaryExcludesCancelledOrdersAndComparesPeriods(): void
    {
        $summary = $this->reports->summary(30);

        self::assertSame(['revenue' => 70000, 'orders' => 2, 'units' => 3, 'customers' => 2, 'aov' => 35000], $summary['current']);
        self::assertSame(['revenue' => 10000, 'orders' => 1, 'units' => 1, 'customers' => 1, 'aov' => 10000], $summary['previous']);
    }

    public function testDailyRevenueIsZeroFilledAndEndsToday(): void
    {
        $days = $this->reports->dailyRevenue(7);

        self::assertCount(7, $days);
        self::assertSame('2026-03-04', $days[0]['date']);
        self::assertSame('2026-03-10', $days[6]['date']);
        self::assertSame(['date' => '2026-03-08', 'revenue' => 50000, 'orders' => 1], $days[4]);
        self::assertSame(0, $days[5]['revenue']);
    }

    public function testRevenueIsGoodsSoldSoTheCategoriesAddUpToTheHeadline(): void
    {
        // Totals include 13% HST (see order() below); revenue does not.
        $categories = array_sum(array_column($this->reports->revenueByCategory(30), 'revenue'));

        self::assertSame($categories, $this->reports->summary(30)['current']['revenue']);
        self::assertSame(70000, $categories);
    }

    public function testThePreviousPeriodIsExactlyAsLongAsTheCurrentOne(): void
    {
        // Current: 2026-02-09 00:00 until now, 2026-03-10 15:00 — 29 days and
        // 15 hours, not 30 days. The previous period is the same length,
        // starting 2026-01-10 09:00.
        $this->order($this->alice, '2026-01-10 08:59:59', 'delivered', [[$this->speaker, 'Speaker', 10000, 1]]);
        $this->order($this->alice, '2026-01-10 09:00:00', 'delivered', [[$this->speaker, 'Speaker', 10000, 1]]);

        self::assertSame(2, $this->reports->summary(30)['previous']['orders']);
    }

    public function testDaysAreTheStoresDaysNotUtcDays(): void
    {
        // 02:00 UTC on 9 March is 22:00 on the 8th in Toronto, where the
        // order page shows it.
        $this->order($this->alice, '2026-03-09 02:00:00', 'delivered', [[$this->speaker, 'Speaker', 10000, 1]]);
        $toronto = new Reports($this->db, $this->clock, new DateTimeZone('America/Toronto'));

        $days = $toronto->dailyRevenue(7);

        self::assertSame('2026-03-10', $days[6]['date']);
        self::assertSame(['date' => '2026-03-08', 'revenue' => 60000, 'orders' => 2], $days[4]);
        self::assertSame(['date' => '2026-03-09', 'revenue' => 0, 'orders' => 0], $days[5]);
        self::assertSame(1, $this->reports->dailyRevenue(7)[5]['orders'], 'In UTC it falls on the 9th');
    }

    public function testCategoryAndProductRankings(): void
    {
        self::assertSame(
            [['name' => 'Phones', 'revenue' => 50000, 'units' => 1], ['name' => 'Audio', 'revenue' => 20000, 'units' => 2]],
            $this->reports->revenueByCategory(30),
        );
        self::assertSame('Speaker', $this->reports->topProducts(30)[0]['name']);
    }

    /** @param list<array{int, string, int, int}> $lines product id, name, unit cents, qty */
    private function order(int $userId, string $at, string $status, array $lines): void
    {
        $subtotal = array_sum(array_map(static fn(array $l): int => $l[2] * $l[3], $lines));
        $tax = intdiv($subtotal * 13, 100);
        $id = $this->db->insert(
            "INSERT INTO orders (user_id, status, subtotal_cents, shipping_cents, tax_cents, total_cents, ship_name, ship_street, ship_city, ship_province, ship_postal_code, created_at, updated_at)
             VALUES (?, ?, ?, 0, ?, ?, 'X', 'Y', 'Z', 'ON', 'N9B 3P4', ?, ?)",
            [$userId, $status, $subtotal, $tax, $subtotal + $tax, $at, $at],
        );
        foreach ($lines as [$productId, $name, $unit, $qty]) {
            $this->db->execute(
                'INSERT INTO order_items (order_id, product_id, product_name, options_label, unit_price_cents, quantity, line_total_cents) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$id, $productId, $name, '', $unit, $qty, $unit * $qty],
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Tests\Support\DatabaseTestCase;
use TechNest\User\UserRepository;

final class UserRepositoryTest extends DatabaseTestCase
{
    public function testUsernamesAndEmailsAreUniqueRegardlessOfCase(): void
    {
        $this->fixtures->user('Quinn');
        $users = new UserRepository($this->db, $this->clock);

        self::assertSame(['username' => true, 'email' => false], $users->taken('quinn', 'new@example.com'));
        self::assertSame(['username' => false, 'email' => true], $users->taken('new', 'QUINN@example.com'));
    }

    public function testListWithStatsAggregatesOrdersAndSpend(): void
    {
        $rae = $this->fixtures->user('rae', admin: true);
        $this->fixtures->user('sam');
        $product = $this->fixtures->product();
        foreach ([['delivered', 1000], ['cancelled', 5000], ['pending', 2500]] as [$status, $total]) {
            $this->db->execute(
                "INSERT INTO orders (user_id, status, subtotal_cents, shipping_cents, tax_cents, total_cents, ship_name, ship_street, ship_city, ship_province, ship_postal_code, created_at, updated_at)
                 VALUES (?, ?, ?, 0, 0, ?, 'R', '1 St', 'City', 'ON', 'N9B 3P4', '2026-03-01 00:00:00', '2026-03-01 00:00:00')",
                [$rae->id, $status, $total, $total],
            );
        }
        unset($product);

        $rows = (new UserRepository($this->db, $this->clock))->listWithStats();

        self::assertSame(['rae', 'sam'], array_map(static fn(array $r): string => $r['user']->username, $rows));
        self::assertSame([true, 3, 3500], [$rows[0]['is_admin'], $rows[0]['orders'], $rows[0]['spent_cents']]);
        self::assertSame([false, 0, 0], [$rows[1]['is_admin'], $rows[1]['orders'], $rows[1]['spent_cents']]);
    }
}

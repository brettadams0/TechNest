<?php

declare(strict_types=1);

namespace TechNest\Order;

use TechNest\Cart\PricedLine;
use TechNest\Catalog\Paginated;
use TechNest\Checkout\ShippingAddress;
use TechNest\Checkout\Totals;
use TechNest\Database\Database;
use TechNest\Database\Row;

final class OrderRepository
{
    private const SELECT = 'SELECT o.*, u.username,
                                   (SELECT COALESCE(SUM(i.quantity), 0) FROM order_items i WHERE i.order_id = o.id) AS item_count
                              FROM orders o
                              JOIN users u ON u.id = o.user_id';

    public function __construct(private readonly Database $db) {}

    public function find(int $id): ?Order
    {
        $row = $this->db->fetchOne(self::SELECT . ' WHERE o.id = ?', [$id]);

        return $row === null ? null : $this->withDetail($row);
    }

    /**
     * An order only if it belongs to this user. Callers turn null into a 404
     * rather than a 403, so order ids cannot be probed for existence.
     */
    public function findForUser(int $id, int $userId): ?Order
    {
        $row = $this->db->fetchOne(self::SELECT . ' WHERE o.id = ? AND o.user_id = ?', [$id, $userId]);

        return $row === null ? null : $this->withDetail($row);
    }

    /** @return list<Order> newest first, without line items */
    public function forUser(int $userId): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC', [$userId]);

        return array_map(static fn(array $r): Order => Order::fromRow($r), $rows);
    }

    /** @return Paginated<Order> */
    public function paginate(?OrderStatus $status, int $page, int $perPage = 20): Paginated
    {
        $where = $status === null ? '' : ' WHERE o.status = ?';
        $params = $status === null ? [] : [$status->value];

        $total = $this->db->fetchInt('SELECT COUNT(*) FROM orders o' . $where, $params);
        $rows = $this->db->fetchAll(
            self::SELECT . $where . ' ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?',
            [...$params, $perPage, ($page - 1) * $perPage],
        );

        return new Paginated(
            array_map(static fn(array $r): Order => Order::fromRow($r), $rows),
            $total,
            $page,
            $perPage,
        );
    }

    /** @return list<Order> */
    public function recent(int $limit = 6): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY o.created_at DESC, o.id DESC LIMIT ?', [$limit]);

        return array_map(static fn(array $r): Order => Order::fromRow($r), $rows);
    }

    /** @param list<PricedLine> $lines */
    public function create(int $userId, Totals $totals, ShippingAddress $address, array $lines, string $now): int
    {
        $orderId = $this->db->insert(
            'INSERT INTO orders (user_id, status, subtotal_cents, shipping_cents, tax_cents, total_cents,
                                 ship_name, ship_street, ship_city, ship_province, ship_postal_code,
                                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, OrderStatus::Pending->value,
                $totals->subtotalCents, $totals->shippingCents, $totals->taxCents, $totals->totalCents,
                $address->name, $address->street, $address->city, $address->province, $address->postalCode,
                $now, $now,
            ],
        );

        // One row per cart line — not one per option, which is what the
        // original checkout wrote (and zero rows for a product with none).
        foreach ($lines as $line) {
            $this->db->execute(
                'INSERT INTO order_items (order_id, product_id, product_name, options_label, unit_price_cents, quantity, line_total_cents)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderId, $line->product->id, $line->product->name, $line->optionsLabel(),
                    $line->unitCents(), $line->quantity, $line->totalCents(),
                ],
            );
        }

        return $orderId;
    }

    /**
     * Move an order between states only if it is still in the state the
     * caller saw. Returns false when someone else changed it first.
     */
    public function compareAndSetStatus(int $id, OrderStatus $from, OrderStatus $to, string $now): bool
    {
        return $this->db->execute(
            'UPDATE orders SET status = ?, updated_at = ? WHERE id = ? AND status = ?',
            [$to->value, $now, $id, $from->value],
        ) === 1;
    }

    public function recordStatusChange(int $orderId, ?OrderStatus $from, OrderStatus $to, ?int $changedBy, string $now): void
    {
        $this->db->execute(
            'INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, changed_at) VALUES (?, ?, ?, ?, ?)',
            [$orderId, $from?->value, $to->value, $changedBy, $now],
        );
    }

    /** @return array<string, int> status value => number of orders, every status present */
    public function countByStatus(): array
    {
        $counts = array_fill_keys(array_map(static fn(OrderStatus $s): string => $s->value, OrderStatus::cases()), 0);
        foreach ($this->db->fetchAll('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            $counts[Row::string($row, 'status')] = Row::int($row, 'n');
        }

        return $counts;
    }

    /** @param array<string, mixed> $row */
    private function withDetail(array $row): Order
    {
        $items = array_map(static fn(array $r): OrderItem => new OrderItem(
            Row::int($r, 'product_id'),
            Row::string($r, 'product_name'),
            Row::string($r, 'options_label'),
            Row::int($r, 'unit_price_cents'),
            Row::int($r, 'quantity'),
            Row::int($r, 'line_total_cents'),
            is_string($r['slug']) ? $r['slug'] : null,
            is_string($r['image']) ? $r['image'] : null,
        ), $this->db->fetchAll(
            'SELECT i.*, p.slug, p.image
               FROM order_items i
               LEFT JOIN products p ON p.id = i.product_id
              WHERE i.order_id = ?
              ORDER BY i.id',
            [Row::int($row, 'id')],
        ));

        $history = array_map(static fn(array $r): StatusChange => new StatusChange(
            is_string($r['from_status']) ? OrderStatus::from($r['from_status']) : null,
            OrderStatus::from(Row::string($r, 'to_status')),
            is_string($r['username']) ? $r['username'] : null,
            Row::string($r, 'changed_at'),
        ), $this->db->fetchAll(
            'SELECT h.from_status, h.to_status, h.changed_at, u.username
               FROM order_status_history h
               LEFT JOIN users u ON u.id = h.changed_by
              WHERE h.order_id = ?
              ORDER BY h.changed_at, h.id',
            [Row::int($row, 'id')],
        ));

        return Order::fromRow($row, $items, $history);
    }
}

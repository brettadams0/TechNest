<?php

declare(strict_types=1);

namespace TechNest\Order;

use TechNest\Checkout\ShippingAddress;
use TechNest\Database\Row;

final class Order
{
    /**
     * @param list<OrderItem>    $items
     * @param list<StatusChange> $history
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $username,
        public readonly OrderStatus $status,
        public readonly int $subtotalCents,
        public readonly int $shippingCents,
        public readonly int $taxCents,
        public readonly int $totalCents,
        public readonly ShippingAddress $shipTo,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly int $itemCount = 0,
        public readonly array $items = [],
        public readonly array $history = [],
    ) {}

    /**
     * @param array<string, mixed> $row
     * @param list<OrderItem>      $items
     * @param list<StatusChange>   $history
     */
    public static function fromRow(array $row, array $items = [], array $history = []): self
    {
        return new self(
            Row::int($row, 'id'),
            Row::int($row, 'user_id'),
            Row::string($row, 'username'),
            OrderStatus::from(Row::string($row, 'status')),
            Row::int($row, 'subtotal_cents'),
            Row::int($row, 'shipping_cents'),
            Row::int($row, 'tax_cents'),
            Row::int($row, 'total_cents'),
            new ShippingAddress(
                Row::string($row, 'ship_name'),
                Row::string($row, 'ship_street'),
                Row::string($row, 'ship_city'),
                Row::string($row, 'ship_province'),
                Row::string($row, 'ship_postal_code'),
            ),
            Row::string($row, 'created_at'),
            Row::string($row, 'updated_at'),
            isset($row['item_count'])
                ? Row::int($row, 'item_count')
                : array_sum(array_map(static fn(OrderItem $i): int => $i->quantity, $items)),
            $items,
            $history,
        );
    }

    public function number(): string
    {
        return sprintf('TN-%05d', $this->id);
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Order\OrderStatus;

final class OrderStatusTest extends TestCase
{
    private const ALLOWED = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /** Every one of the 25 from→to pairs, so nothing is allowed by accident. */
    #[DataProvider('everyPair')]
    public function testTransitionTable(OrderStatus $from, OrderStatus $to): void
    {
        self::assertSame(
            in_array($to->value, self::ALLOWED[$from->value], true),
            $from->canTransitionTo($to),
            "{$from->value} → {$to->value}",
        );
    }

    /** @return iterable<string, array{OrderStatus, OrderStatus}> */
    public static function everyPair(): iterable
    {
        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                yield "{$from->value} to {$to->value}" => [$from, $to];
            }
        }
    }

    public function testShippedOrdersCanNoLongerBeCancelled(): void
    {
        self::assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
    }

    public function testOnlyDeliveredAndCancelledAreFinal(): void
    {
        $final = array_values(array_filter(OrderStatus::cases(), static fn(OrderStatus $s): bool => $s->isFinal()));

        self::assertSame([OrderStatus::Delivered, OrderStatus::Cancelled], $final);
    }

    public function testCustomersMayOnlyCancelPendingOrders(): void
    {
        foreach (OrderStatus::cases() as $status) {
            self::assertSame($status === OrderStatus::Pending, $status->isCustomerCancellable());
        }
    }
}

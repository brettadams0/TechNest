<?php

declare(strict_types=1);

namespace TechNest\Order;

/**
 * The order lifecycle as an explicit state machine.
 *
 *   pending ──► processing ──► shipped ──► delivered
 *      │             │
 *      └─────────────┴──► cancelled   (stock is returned)
 *
 * The original admin page accepted any string as a status, so an order could
 * jump from "delivered" back to "pending", or be "cancelled" after it had
 * shipped without its stock ever coming back.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return list<self> */
    public function nextStates(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->nextStates(), true);
    }

    public function isFinal(): bool
    {
        return $this->nextStates() === [];
    }

    /** Customers may cancel their own order until it starts being prepared. */
    public function isCustomerCancellable(): bool
    {
        return $this === self::Pending;
    }

    /** Statuses whose orders count as revenue. */
    public function countsAsRevenue(): bool
    {
        return $this !== self::Cancelled;
    }
}

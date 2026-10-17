<?php

declare(strict_types=1);

namespace TechNest\Order;

use TechNest\Database\Database;
use TechNest\Support\Clock;

/**
 * Status changes, each in one transaction: validate the transition, apply it
 * only if the order is still in the state we read (so two admins clicking at
 * once cannot both win), return stock on cancellation, and write the audit
 * row.
 */
final class OrderService
{
    public function __construct(
        private readonly Database $db,
        private readonly OrderRepository $orders,
        private readonly Clock $clock,
    ) {}

    /** @throws InvalidTransition */
    public function transition(int $orderId, OrderStatus $to, ?int $actorId): Order
    {
        return $this->db->transaction(function () use ($orderId, $to, $actorId): Order {
            $order = $this->orders->find($orderId) ?? throw new InvalidTransition('That order does not exist.');

            if (!$order->status->canTransitionTo($to)) {
                throw new InvalidTransition(sprintf(
                    'Order %s is %s and cannot be marked %s.',
                    $order->number(),
                    $order->status->value,
                    $to->value,
                ));
            }

            $now = $this->clock->now()->format('Y-m-d H:i:s');
            if (!$this->orders->compareAndSetStatus($order->id, $order->status, $to, $now)) {
                throw new InvalidTransition("Order {$order->number()} was changed by someone else. Reload and try again.");
            }

            if ($to === OrderStatus::Cancelled) {
                foreach ($order->items as $item) {
                    $this->db->execute('UPDATE products SET stock = stock + ? WHERE id = ?', [$item->quantity, $item->productId]);
                }
            }

            $this->orders->recordStatusChange($order->id, $order->status, $to, $actorId, $now);

            return $this->orders->find($order->id) ?? $order;
        });
    }

    /** @throws InvalidTransition */
    public function cancelByCustomer(int $orderId, int $userId): Order
    {
        $order = $this->orders->findForUser($orderId, $userId) ?? throw new InvalidTransition('That order does not exist.');

        if (!$order->status->isCustomerCancellable()) {
            throw new InvalidTransition("Order {$order->number()} is already {$order->status->value} and can no longer be cancelled online.");
        }

        return $this->transition($order->id, OrderStatus::Cancelled, $userId);
    }
}

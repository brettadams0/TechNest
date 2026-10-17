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

    /**
     * @param OrderStatus|null $requiredFrom only apply the change if the order
     *                                       is still in this state, for callers
     *                                       that checked it before calling
     *
     * @throws InvalidTransition
     */
    public function transition(int $orderId, OrderStatus $to, ?int $actorId, ?OrderStatus $requiredFrom = null): Order
    {
        return $this->db->transaction(function () use ($orderId, $to, $actorId, $requiredFrom): Order {
            $order = $this->orders->find($orderId) ?? throw new InvalidTransition('That order does not exist.');

            if ($requiredFrom !== null && $order->status !== $requiredFrom) {
                throw new InvalidTransition("Order {$order->number()} is now {$order->status->value}, so it was not changed.");
            }

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
                // Product-id order, the same as checkout, so the two cannot deadlock.
                $items = $order->items;
                usort($items, static fn(OrderItem $a, OrderItem $b): int => $a->productId <=> $b->productId);
                foreach ($items as $item) {
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

        // Pass the status we just checked: an admin may move the order on
        // between this read and the transaction, and a customer must not be
        // able to cancel an order that has started processing.
        return $this->transition($order->id, OrderStatus::Cancelled, $userId, $order->status);
    }
}

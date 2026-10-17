<?php

declare(strict_types=1);

namespace TechNest\Checkout;

use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Cart\PricedLine;
use TechNest\Database\Database;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderStatus;
use TechNest\Support\Clock;
use TechNest\User\User;

/**
 * Places an order as a single transaction:
 *
 *  1. re-price the cart from the database (the session holds no prices) and
 *     check it is still the cart the customer reviewed;
 *  2. take stock with a conditional UPDATE … WHERE stock >= qty, which is
 *     atomic, so two customers racing for the last unit cannot both get it;
 *  3. write the order, one line per cart line, with name/options/price
 *     snapshotted so later catalog edits never rewrite history;
 *  4. record the first status-history entry.
 *
 * Any failure rolls back everything, including stock already taken for
 * earlier lines. The cart is cleared only after the commit.
 */
final class CheckoutService
{
    public function __construct(
        private readonly Database $db,
        private readonly CartPricer $pricer,
        private readonly OrderRepository $orders,
        private readonly Clock $clock,
    ) {}

    /**
     * @param string|null $reviewed the PricedCart::fingerprint() of the cart
     *                              the customer was shown; null skips the check
     *
     * @throws CheckoutException
     */
    public function placeOrder(User $customer, Cart $cart, ShippingAddress $address, ?string $reviewed = null): int
    {
        $orderId = $this->db->transaction(function () use ($customer, $cart, $address, $reviewed): int {
            $priced = $this->pricer->price($cart);

            if ($priced->notices !== []) {
                // Something changed since the customer reviewed the cart:
                // stop and show them, rather than charge for a different order.
                throw new CheckoutException(implode(' ', $priced->notices));
            }
            if ($priced->isEmpty()) {
                throw new CheckoutException('Your cart is empty.');
            }
            if ($reviewed !== null && !hash_equals($priced->fingerprint(), $reviewed)) {
                // A price changed, or the cart was changed in another tab.
                throw new CheckoutException('Your cart changed since you reviewed it. Please check it before placing your order.');
            }

            // Lock rows in product-id order: two checkouts taking the same
            // products in different orders would otherwise deadlock on MySQL.
            $byProduct = $priced->lines;
            usort($byProduct, static fn(PricedLine $a, PricedLine $b): int => $a->product->id <=> $b->product->id);

            foreach ($byProduct as $line) {
                $taken = $this->db->execute(
                    'UPDATE products SET stock = stock - ? WHERE id = ? AND is_active = 1 AND stock >= ?',
                    [$line->quantity, $line->product->id, $line->quantity],
                );
                if ($taken !== 1) {
                    throw new CheckoutException("Sorry — {$line->product->name} sold out while you were checking out.");
                }
            }

            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $orderId = $this->orders->create($customer->id, $priced->totals, $address, $priced->lines, $now);
            $this->orders->recordStatusChange($orderId, null, OrderStatus::Pending, $customer->id, $now);

            return $orderId;
        });

        $cart->clear();

        return $orderId;
    }
}

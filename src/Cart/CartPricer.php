<?php

declare(strict_types=1);

namespace TechNest\Cart;

use TechNest\Catalog\InvalidSelection;
use TechNest\Catalog\OptionSelection;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\PriceCalculator;

/**
 * Prices the session cart against the live catalog, and reconciles it while
 * doing so: lines for products that were archived, options that were
 * deleted, or stock that ran out are removed or reduced, and each change is
 * reported back as a notice the customer will see.
 */
final class CartPricer
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly PriceCalculator $calculator,
    ) {}

    public function price(Cart $cart): PricedCart
    {
        $lines = $cart->lines();
        $products = $this->products->findMany(array_map(static fn(CartLine $l): int => $l->productId, $lines));

        $priced = [];
        $notices = [];
        $reserved = [];

        foreach ($lines as $line) {
            $product = $products[$line->productId] ?? null;
            if ($product === null || !$product->isActive) {
                $cart->remove($line->key);
                $notices[] = 'An item in your cart is no longer sold and was removed.';
                continue;
            }

            try {
                $options = OptionSelection::resolveIds($product, $line->optionIds);
            } catch (InvalidSelection $e) {
                $cart->remove($line->key);
                $notices[] = $e->getMessage() . ' It was removed from your cart.';
                continue;
            }

            // Stock is shared between lines of the same product in different
            // configurations, so count what earlier lines already claimed.
            $available = $product->stock - ($reserved[$product->id] ?? 0);
            if ($available <= 0) {
                $cart->remove($line->key);
                $notices[] = "{$product->name} is sold out and was removed from your cart.";
                continue;
            }

            $quantity = $line->quantity;
            if ($quantity > $available) {
                $quantity = $available;
                $cart->update($line->key, $quantity);
                $notices[] = "Only {$available} {$product->name} left in stock; your quantity was reduced.";
            }

            $reserved[$product->id] = ($reserved[$product->id] ?? 0) + $quantity;
            $priced[] = new PricedLine($line->key, $product, $options, $quantity);
        }

        $subtotal = array_sum(array_map(static fn(PricedLine $l): int => $l->totalCents(), $priced));

        return new PricedCart($priced, $this->calculator->totals($subtotal), array_values(array_unique($notices)));
    }
}

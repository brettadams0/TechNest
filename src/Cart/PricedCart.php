<?php

declare(strict_types=1);

namespace TechNest\Cart;

use TechNest\Checkout\Totals;

final class PricedCart
{
    /**
     * @param list<PricedLine> $lines
     * @param list<string>     $notices what changed while reconciling the
     *                                  cart against the catalog
     */
    public function __construct(
        public readonly array $lines,
        public readonly Totals $totals,
        public readonly array $notices = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * A digest of exactly what the customer is shown: each line, its quantity
     * and unit price, and the total. The checkout form carries it, so an order
     * is only placed for the cart the customer actually reviewed.
     */
    public function fingerprint(): string
    {
        $lines = array_map(
            static fn(PricedLine $l): array => [$l->key, $l->quantity, $l->unitCents()],
            $this->lines,
        );

        return hash('sha256', json_encode([$lines, $this->totals->totalCents], JSON_THROW_ON_ERROR));
    }

    public function itemCount(): int
    {
        return array_sum(array_map(static fn(PricedLine $l): int => $l->quantity, $this->lines));
    }
}

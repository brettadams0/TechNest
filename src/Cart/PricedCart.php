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

    public function itemCount(): int
    {
        return array_sum(array_map(static fn(PricedLine $l): int => $l->quantity, $this->lines));
    }
}

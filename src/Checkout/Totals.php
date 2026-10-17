<?php

declare(strict_types=1);

namespace TechNest\Checkout;

final class Totals
{
    public function __construct(
        public readonly int $subtotalCents,
        public readonly int $shippingCents,
        public readonly int $taxCents,
        public readonly int $totalCents,
        public readonly int $taxBasisPoints = 0,
    ) {}

    /** "13%" — the rate the tax line was calculated at. */
    public function taxRate(): string
    {
        return rtrim(rtrim(number_format($this->taxBasisPoints / 100, 2), '0'), '.') . '%';
    }
}

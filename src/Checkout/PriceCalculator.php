<?php

declare(strict_types=1);

namespace TechNest\Checkout;

/**
 * Shipping and tax rules, in integer cents.
 *
 * Defaults model an Ontario store: flat-rate shipping that becomes free above
 * a threshold, and 13% HST charged on goods and shipping alike. Tax is
 * rounded half-up to the cent once, on the order, not per line.
 */
final class PriceCalculator
{
    public function __construct(
        public readonly int $taxBasisPoints = 1300,
        public readonly int $shippingCents = 1500,
        public readonly int $freeShippingThresholdCents = 10000,
    ) {}

    public function totals(int $subtotalCents): Totals
    {
        if ($subtotalCents <= 0) {
            return new Totals(0, 0, 0, 0, $this->taxBasisPoints);
        }

        $shipping = $this->shippingFor($subtotalCents);
        $tax = intdiv(($subtotalCents + $shipping) * $this->taxBasisPoints + 5000, 10000);

        return new Totals($subtotalCents, $shipping, $tax, $subtotalCents + $shipping + $tax, $this->taxBasisPoints);
    }

    public function shippingFor(int $subtotalCents): int
    {
        return $subtotalCents >= $this->freeShippingThresholdCents ? 0 : $this->shippingCents;
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Checkout\PriceCalculator;

final class PriceCalculatorTest extends TestCase
{
    public function testAnEmptyCartCostsNothing(): void
    {
        $totals = (new PriceCalculator())->totals(0);

        self::assertSame([0, 0, 0, 0], [$totals->subtotalCents, $totals->shippingCents, $totals->taxCents, $totals->totalCents]);
    }

    public function testSmallOrdersPayFlatShippingAndTaxIsChargedOnIt(): void
    {
        $totals = (new PriceCalculator())->totals(9000);

        self::assertSame(1500, $totals->shippingCents);
        self::assertSame(1365, $totals->taxCents); // 13% of 105.00
        self::assertSame(11865, $totals->totalCents);
    }

    public function testShippingIsFreeFromTheThresholdUp(): void
    {
        $calculator = new PriceCalculator();

        self::assertSame(1500, $calculator->totals(9999)->shippingCents);
        self::assertSame(0, $calculator->totals(10000)->shippingCents);
    }

    public function testTaxRoundsHalfUpToTheCent(): void
    {
        // 13% of $15.50 is 201.5 cents.
        $totals = (new PriceCalculator(shippingCents: 1500, freeShippingThresholdCents: 100000))->totals(50);

        self::assertSame(202, $totals->taxCents);
    }

    public function testRulesAreConfigurable(): void
    {
        $totals = (new PriceCalculator(taxBasisPoints: 500, shippingCents: 0))->totals(2000);

        self::assertSame(100, $totals->taxCents);
        self::assertSame('5%', $totals->taxRate());
        self::assertSame(2100, $totals->totalCents);
    }

    public function testTotalsAlwaysAddUp(): void
    {
        $calculator = new PriceCalculator();
        foreach ([1, 99, 9999, 10000, 123457, 999999] as $subtotal) {
            $t = $calculator->totals($subtotal);
            self::assertSame($t->subtotalCents + $t->shippingCents + $t->taxCents, $t->totalCents);
        }
    }
}

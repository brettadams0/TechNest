<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Catalog\InvalidSelection;
use TechNest\Catalog\OptionSelection;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductOption;

final class OptionSelectionTest extends TestCase
{
    private Product $phone;

    protected function setUp(): void
    {
        $this->phone = new Product(1, 1, 'phones', 'Phones', 'phone', 'Phone', '', null, 99900, 5, false, true, '2026-01-01 00:00:00', [
            new ProductOption(10, 1, 'Color', 'Black', 0),
            new ProductOption(11, 1, 'Color', 'White', 0),
            new ProductOption(12, 1, 'Storage', '128GB', 0),
            new ProductOption(13, 1, 'Storage', '256GB', 10000),
        ]);
    }

    public function testResolvesOneChoicePerGroupInGroupOrder(): void
    {
        $chosen = OptionSelection::resolve($this->phone, ['Storage' => 13, 'Color' => 11]);

        self::assertSame([11, 13], array_map(static fn(ProductOption $o): int => $o->id, $chosen));
        self::assertSame(10000, OptionSelection::extraCents($chosen));
        self::assertSame('Color: White · Storage: 256GB', OptionSelection::label($chosen));
    }

    public function testEveryGroupMustBeChosen(): void
    {
        // The original store let you skip "Storage" and pay the base price.
        $this->expectException(InvalidSelection::class);
        $this->expectExceptionMessage('Choose a Storage');
        OptionSelection::resolve($this->phone, ['Color' => 10]);
    }

    public function testAChoiceFromAnotherGroupIsRejected(): void
    {
        $this->expectException(InvalidSelection::class);
        OptionSelection::resolve($this->phone, ['Color' => 12, 'Storage' => 13]);
    }

    public function testAnOptionIdFromAnotherProductIsRejected(): void
    {
        $this->expectException(InvalidSelection::class);
        OptionSelection::resolve($this->phone, ['Color' => 10, 'Storage' => 999]);
    }

    public function testCartLinesWithTwoChoicesForOneGroupAreRejected(): void
    {
        $this->expectException(InvalidSelection::class);
        OptionSelection::resolveIds($this->phone, [10, 11, 12]);
    }

    public function testProductsWithoutOptionsResolveToNothing(): void
    {
        $plain = new Product(2, 1, 'phones', 'Phones', 'plain', 'Plain', '', null, 500, 5, false, true, '2026-01-01 00:00:00');

        self::assertSame([], OptionSelection::resolveIds($plain, []));
    }
}

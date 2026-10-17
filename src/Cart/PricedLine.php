<?php

declare(strict_types=1);

namespace TechNest\Cart;

use TechNest\Catalog\OptionSelection;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductOption;

final class PricedLine
{
    /** @param list<ProductOption> $options */
    public function __construct(
        public readonly string $key,
        public readonly Product $product,
        public readonly array $options,
        public readonly int $quantity,
    ) {}

    public function unitCents(): int
    {
        return $this->product->priceCents + OptionSelection::extraCents($this->options);
    }

    public function totalCents(): int
    {
        return $this->unitCents() * $this->quantity;
    }

    public function optionsLabel(): string
    {
        return OptionSelection::label($this->options);
    }
}

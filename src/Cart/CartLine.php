<?php

declare(strict_types=1);

namespace TechNest\Cart;

final class CartLine
{
    /** @param list<int> $optionIds */
    public function __construct(
        public readonly string $key,
        public readonly int $productId,
        public readonly array $optionIds,
        public readonly int $quantity,
    ) {}
}

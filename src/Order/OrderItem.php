<?php

declare(strict_types=1);

namespace TechNest\Order;

final class OrderItem
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $optionsLabel,
        public readonly int $unitPriceCents,
        public readonly int $quantity,
        public readonly int $lineTotalCents,
        public readonly ?string $productSlug = null,
        public readonly ?string $productImage = null,
    ) {}
}

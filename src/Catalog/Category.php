<?php

declare(strict_types=1);

namespace TechNest\Catalog;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly int $productCount = 0,
    ) {}
}

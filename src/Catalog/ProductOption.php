<?php

declare(strict_types=1);

namespace TechNest\Catalog;

use TechNest\Database\Row;

final class ProductOption
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $group,
        public readonly string $value,
        public readonly int $extraCents,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            Row::int($row, 'id'),
            Row::int($row, 'product_id'),
            Row::string($row, 'group_name'),
            Row::string($row, 'value'),
            Row::int($row, 'extra_cents'),
        );
    }

    public function label(): string
    {
        return $this->group . ': ' . $this->value;
    }
}

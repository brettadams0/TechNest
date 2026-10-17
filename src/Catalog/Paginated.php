<?php

declare(strict_types=1);

namespace TechNest\Catalog;

/**
 * @template T
 */
final class Paginated
{
    /** @param list<T> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function firstItem(): int
    {
        return $this->total === 0 ? 0 : ($this->page - 1) * $this->perPage + 1;
    }

    public function lastItem(): int
    {
        return min($this->total, $this->page * $this->perPage);
    }
}

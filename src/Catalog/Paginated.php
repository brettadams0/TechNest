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

    /**
     * The page numbers to link to: the first, the last, and two either side
     * of the current one, with null where a run of pages is left out.
     *
     * @return list<int|null>
     */
    public function window(int $around = 2): array
    {
        $last = $this->lastPage();
        $pages = [];
        $previous = 0;
        for ($i = 1; $i <= $last; ++$i) {
            if ($i !== 1 && $i !== $last && abs($i - $this->page) > $around) {
                continue;
            }
            if ($i - $previous === 2) {
                $pages[] = $i - 1;
            } elseif ($i - $previous > 2) {
                $pages[] = null;
            }
            $pages[] = $i;
            $previous = $i;
        }

        return $pages;
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

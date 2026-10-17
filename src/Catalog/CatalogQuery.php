<?php

declare(strict_types=1);

namespace TechNest\Catalog;

use TechNest\Http\Request;

/**
 * The catalog's search, filter, sort and page, parsed from the query string
 * once and validated, so the repository only ever sees known values.
 */
final class CatalogQuery
{
    public const SORTS = [
        'featured' => 'Featured',
        'price-asc' => 'Price: low to high',
        'price-desc' => 'Price: high to low',
        'newest' => 'Newest',
        'name' => 'Name: A to Z',
    ];

    public const PER_PAGE = 12;

    public function __construct(
        public readonly string $search = '',
        public readonly string $category = '',
        public readonly string $sort = 'featured',
        public readonly bool $inStockOnly = false,
        public readonly int $page = 1,
        public readonly int $perPage = self::PER_PAGE,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->query('sort', 'featured');

        return new self(
            search: mb_substr($request->query('q'), 0, 100),
            category: preg_match('/^[a-z0-9-]{1,60}$/', $request->query('category')) === 1 ? $request->query('category') : '',
            sort: array_key_exists($sort, self::SORTS) ? $sort : 'featured',
            inStockOnly: $request->query('in_stock') === '1',
            page: $request->queryPage(),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function isFiltered(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->inStockOnly;
    }

    /**
     * Query-string parameters for a link that keeps the current filters and
     * changes some of them. Defaults are omitted to keep URLs short.
     *
     * @param array<string, string|int|null> $changes
     *
     * @return array<string, string|int>
     */
    public function params(array $changes = []): array
    {
        $params = array_merge([
            'q' => $this->search,
            'category' => $this->category,
            'sort' => $this->sort === 'featured' ? '' : $this->sort,
            'in_stock' => $this->inStockOnly ? '1' : '',
            'page' => $this->page === 1 ? '' : $this->page,
        ], $changes);

        if (($params['page'] ?? null) === 1) {
            unset($params['page']);
        }

        return array_filter($params, static fn($v): bool => $v !== '' && $v !== null);
    }
}

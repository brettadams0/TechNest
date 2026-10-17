<?php

declare(strict_types=1);

namespace TechNest\Catalog;

use TechNest\Database\Database;
use TechNest\Database\Row;

final class CategoryRepository
{
    public function __construct(private readonly Database $db) {}

    /** @return list<Category> with the number of active products in each */
    public function all(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT c.id, c.slug, c.name, COUNT(p.id) AS product_count
               FROM categories c
               LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
              GROUP BY c.id, c.slug, c.name, c.sort_order
              ORDER BY c.sort_order, c.name',
        );

        return array_map(static fn(array $r): Category => new Category(
            Row::int($r, 'id'),
            Row::string($r, 'slug'),
            Row::string($r, 'name'),
            Row::int($r, 'product_count'),
        ), $rows);
    }

    public function findBySlug(string $slug): ?Category
    {
        $row = $this->db->fetchOne('SELECT id, slug, name FROM categories WHERE slug = ?', [$slug]);

        return $row === null ? null : new Category(Row::int($row, 'id'), Row::string($row, 'slug'), Row::string($row, 'name'));
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Catalog;

use TechNest\Database\Database;
use TechNest\Database\Row;
use TechNest\Support\Clock;

final class ProductRepository
{
    private const SELECT = 'SELECT p.id, p.category_id, c.slug AS category_slug, c.name AS category_name,
                                   p.slug, p.name, p.description, p.image, p.price_cents, p.stock,
                                   p.is_featured, p.is_active, p.created_at
                              FROM products p
                              JOIN categories c ON c.id = p.category_id';

    /** Whitelisted ORDER BY clauses; user input only ever selects a key. */
    private const ORDER_BY = [
        'featured' => 'p.is_featured DESC, p.id ASC',
        'price-asc' => 'p.price_cents ASC, p.id ASC',
        'price-desc' => 'p.price_cents DESC, p.id ASC',
        'newest' => 'p.created_at DESC, p.id DESC',
        'name' => 'p.name ASC',
    ];

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

    /** @return Paginated<Product> */
    public function search(CatalogQuery $query): Paginated
    {
        $where = ['p.is_active = 1'];
        $params = [];

        if ($query->search !== '') {
            $where[] = "(p.name LIKE ? ESCAPE '!' OR p.description LIKE ? ESCAPE '!' OR c.name LIKE ? ESCAPE '!')";
            $like = '%' . self::escapeLike($query->search) . '%';
            array_push($params, $like, $like, $like);
        }
        if ($query->category !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $query->category;
        }
        if ($query->inStockOnly) {
            $where[] = 'p.stock > 0';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $total = $this->db->fetchInt(
            'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id' . $whereSql,
            $params,
        );

        $rows = $this->db->fetchAll(
            self::SELECT . $whereSql . ' ORDER BY ' . self::ORDER_BY[$query->sort] . ' LIMIT ? OFFSET ?',
            [...$params, $query->perPage, $query->offset()],
        );

        return new Paginated($this->hydrate($rows, withOptions: true), $total, $query->page, $query->perPage);
    }

    /** @return list<Product> */
    public function featured(int $limit = 8): array
    {
        $rows = $this->db->fetchAll(
            self::SELECT . ' WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.id LIMIT ?',
            [$limit],
        );

        return $this->hydrate($rows, withOptions: true);
    }

    /** @return list<Product> other active products in the same category */
    public function related(Product $product, int $limit = 4): array
    {
        $rows = $this->db->fetchAll(
            self::SELECT . ' WHERE p.is_active = 1 AND p.category_id = ? AND p.id <> ? ORDER BY p.is_featured DESC, p.id LIMIT ?',
            [$product->categoryId, $product->id, $limit],
        );

        return $this->hydrate($rows, withOptions: true);
    }

    public function findBySlug(string $slug, bool $includeArchived = false): ?Product
    {
        $row = $this->db->fetchOne(
            self::SELECT . ' WHERE p.slug = ?' . ($includeArchived ? '' : ' AND p.is_active = 1'),
            [$slug],
        );

        return $row === null ? null : $this->hydrate([$row], withOptions: true)[0];
    }

    public function find(int $id): ?Product
    {
        $row = $this->db->fetchOne(self::SELECT . ' WHERE p.id = ?', [$id]);

        return $row === null ? null : $this->hydrate([$row], withOptions: true)[0];
    }

    /**
     * Products by id, archived ones included (the caller decides what an
     * archived product in a cart means), keyed by id.
     *
     * @param list<int> $ids
     *
     * @return array<int, Product>
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(self::SELECT . " WHERE p.id IN ({$placeholders})", $ids);

        $byId = [];
        foreach ($this->hydrate($rows, withOptions: true) as $product) {
            $byId[$product->id] = $product;
        }

        return $byId;
    }

    /** @return list<Product> every product, archived included, for the admin table */
    public function adminList(string $search = ''): array
    {
        $params = [];
        $where = '';
        if ($search !== '') {
            $where = " WHERE p.name LIKE ? ESCAPE '!'";
            $params[] = '%' . self::escapeLike($search) . '%';
        }

        return $this->hydrate(
            $this->db->fetchAll(self::SELECT . $where . ' ORDER BY p.is_active DESC, c.sort_order, p.name', $params),
            withOptions: false,
        );
    }

    /** @return list<Product> active products at or below the low-stock line */
    public function lowStock(int $threshold = Product::LOW_STOCK): array
    {
        return $this->hydrate(
            $this->db->fetchAll(self::SELECT . ' WHERE p.is_active = 1 AND p.stock <= ? ORDER BY p.stock, p.name', [$threshold]),
            withOptions: false,
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return $this->db->fetchInt(
            'SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?',
            [$slug, $exceptId ?? 0],
        ) > 0;
    }

    /** @param array{category_id: int, slug: string, name: string, description: string, image: ?string, price_cents: int, stock: int, is_featured: bool} $data */
    public function create(array $data): int
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO products (category_id, slug, name, description, image, price_cents, stock, is_featured, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
            [
                $data['category_id'], $data['slug'], $data['name'], $data['description'], $data['image'],
                $data['price_cents'], $data['stock'], $data['is_featured'] ? 1 : 0, $now, $now,
            ],
        );
    }

    /**
     * Save the edit form. Stock moves underneath an open form (checkouts take
     * it, cancellations return it), so it is only written when the admin
     * changed it, and then only if it still holds the value the form showed.
     * Writing it back unconditionally would undo every sale made meanwhile.
     *
     * @param array{category_id: int, slug: string, name: string, description: string, image: ?string, price_cents: int, stock: int, is_featured: bool} $data
     * @param int $stockSeen the stock the form was showing
     *
     * @return bool false if stock was changed meanwhile; nothing is saved then
     */
    public function update(int $id, array $data, int $stockSeen): bool
    {
        $params = [
            $data['category_id'], $data['slug'], $data['name'], $data['description'], $data['image'],
            $data['price_cents'], $data['is_featured'] ? 1 : 0, $this->now(),
        ];
        $set = 'category_id = ?, slug = ?, name = ?, description = ?, image = ?, price_cents = ?, is_featured = ?, updated_at = ?';

        if ($data['stock'] === $stockSeen) {
            $this->db->execute("UPDATE products SET {$set} WHERE id = ?", [...$params, $id]);

            return true;
        }

        return $this->db->execute(
            "UPDATE products SET {$set}, stock = ? WHERE id = ? AND stock = ?",
            [...$params, $data['stock'], $id, $stockSeen],
        ) === 1;
    }

    /**
     * Archive or restore. Products are never hard-deleted: order lines keep a
     * foreign key to them, and "delete" in the original version simply failed
     * (or orphaned history) for anything that had ever been sold.
     */
    public function setActive(int $id, bool $active): void
    {
        $this->db->execute(
            'UPDATE products SET is_active = ?, updated_at = ? WHERE id = ?',
            [$active ? 1 : 0, $this->now(), $id],
        );
    }

    public function addOption(int $productId, string $group, string $value, int $extraCents): void
    {
        $sort = $this->db->fetchInt('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_options WHERE product_id = ?', [$productId]);
        $this->db->execute(
            'INSERT INTO product_options (product_id, group_name, value, extra_cents, sort_order) VALUES (?, ?, ?, ?, ?)',
            [$productId, $group, $value, $extraCents, $sort],
        );
    }

    public function optionExists(int $productId, string $group, string $value): bool
    {
        return $this->db->fetchInt(
            'SELECT COUNT(*) FROM product_options WHERE product_id = ? AND group_name = ? AND value = ?',
            [$productId, $group, $value],
        ) > 0;
    }

    public function deleteOption(int $productId, int $optionId): bool
    {
        return $this->db->execute('DELETE FROM product_options WHERE id = ? AND product_id = ?', [$optionId, $productId]) > 0;
    }

    /** Escape LIKE wildcards so a search for "100%" means the literal text. */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<Product>
     */
    private function hydrate(array $rows, bool $withOptions): array
    {
        if ($rows === []) {
            return [];
        }

        $options = $withOptions ? $this->optionsFor(array_map(static fn(array $r): int => Row::int($r, 'id'), $rows)) : [];

        return array_map(
            static fn(array $row): Product => Product::fromRow($row, $options[Row::int($row, 'id')] ?? []),
            $rows,
        );
    }

    /**
     * One query for the options of every product on the page, rather than one
     * per product (the original cart page ran a query per option per line).
     *
     * @param list<int> $productIds
     *
     * @return array<int, list<ProductOption>>
     */
    private function optionsFor(array $productIds): array
    {
        $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id, product_id, group_name, value, extra_cents
               FROM product_options
              WHERE product_id IN ({$placeholders})
              ORDER BY product_id, sort_order, id",
            $productIds,
        );

        $grouped = [];
        foreach ($rows as $row) {
            $option = ProductOption::fromRow($row);
            $grouped[$option->productId][] = $option;
        }

        return $grouped;
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}

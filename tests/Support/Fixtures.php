<?php

declare(strict_types=1);

namespace TechNest\Tests\Support;

use TechNest\Catalog\Product;
use TechNest\Catalog\ProductRepository;
use TechNest\Database\Database;
use TechNest\Support\Clock;
use TechNest\User\User;
use TechNest\User\UserRepository;

/** Small builders for the rows tests need, with sensible defaults. */
final class Fixtures
{
    public const PASSWORD = 'correct-horse-battery';

    private int $sequence = 0;

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
    ) {}

    public function category(string $slug = 'phones', string $name = 'Phones'): int
    {
        $existing = $this->db->fetchValue('SELECT id FROM categories WHERE slug = ?', [$slug]);
        if (is_numeric($existing)) {
            return (int) $existing;
        }

        return $this->db->insert('INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, 0)', [$slug, $name]);
    }

    /**
     * @param array{name?: string, slug?: string, category?: string, price?: int, stock?: int, featured?: bool, active?: bool, description?: string, options?: array<string, list<array{0: string, 1: int}>>} $attributes
     */
    public function product(array $attributes = []): Product
    {
        ++$this->sequence;
        $name = $attributes['name'] ?? "Product {$this->sequence}";
        $products = new ProductRepository($this->db, $this->clock);

        $id = $products->create([
            'category_id' => $this->category($attributes['category'] ?? 'phones', ucfirst($attributes['category'] ?? 'phones')),
            'slug' => $attributes['slug'] ?? 'product-' . $this->sequence,
            'name' => $name,
            'description' => $attributes['description'] ?? "Description of {$name}",
            'image' => null,
            'price_cents' => $attributes['price'] ?? 10000,
            'stock' => $attributes['stock'] ?? 10,
            'is_featured' => $attributes['featured'] ?? false,
        ]);

        foreach ($attributes['options'] ?? [] as $group => $values) {
            foreach ($values as [$value, $extra]) {
                $products->addOption($id, $group, $value, $extra);
            }
        }

        if (($attributes['active'] ?? true) === false) {
            $products->setActive($id, false);
        }

        return $products->find($id) ?? throw new \LogicException('fixture product missing');
    }

    /** A product with two option groups, the case the original checkout got wrong. */
    public function phone(int $stock = 10): Product
    {
        return $this->product([
            'name' => 'Pixel Test',
            'slug' => 'pixel-test',
            'price' => 99900,
            'stock' => $stock,
            'options' => [
                'Color' => [['Black', 0], ['White', 0]],
                'Storage' => [['128GB', 0], ['256GB', 10000]],
            ],
        ]);
    }

    public function user(string $username = 'alice', bool $admin = false, bool $active = true, string $password = self::PASSWORD): User
    {
        $users = new UserRepository($this->db, $this->clock);
        $user = $users->create($username, $username . '@example.com', password_hash($password, PASSWORD_DEFAULT));
        if ($admin) {
            $users->grantAdmin($user->id, null);
        }
        if (!$active) {
            $users->setActive($user->id, false);
        }

        return $users->find($user->id) ?? $user;
    }

    public function stock(int $productId): int
    {
        return $this->db->fetchInt('SELECT stock FROM products WHERE id = ?', [$productId]);
    }

    public function count(string $table): int
    {
        return $this->db->fetchInt("SELECT COUNT(*) FROM {$table}");
    }
}

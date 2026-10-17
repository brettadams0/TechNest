<?php

declare(strict_types=1);

namespace TechNest\Catalog;

use TechNest\Database\Row;

final class Product
{
    public const LOW_STOCK = 5;

    /** @param list<ProductOption> $options */
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly string $categorySlug,
        public readonly string $categoryName,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $image,
        public readonly int $priceCents,
        public readonly int $stock,
        public readonly bool $isFeatured,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly array $options = [],
    ) {}

    /**
     * @param array<string, mixed> $row
     * @param list<ProductOption>  $options
     */
    public static function fromRow(array $row, array $options = []): self
    {
        $image = $row['image'] ?? null;

        return new self(
            Row::int($row, 'id'),
            Row::int($row, 'category_id'),
            Row::string($row, 'category_slug'),
            Row::string($row, 'category_name'),
            Row::string($row, 'slug'),
            Row::string($row, 'name'),
            Row::string($row, 'description'),
            is_string($image) && $image !== '' ? $image : null,
            Row::int($row, 'price_cents'),
            Row::int($row, 'stock'),
            Row::bool($row, 'is_featured'),
            Row::bool($row, 'is_active'),
            Row::string($row, 'created_at'),
            $options,
        );
    }

    public function imageUrl(): string
    {
        return '/assets/img/products/' . ($this->image ?? 'placeholder.svg');
    }

    public function url(): string
    {
        return '/products/' . $this->slug;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= self::LOW_STOCK;
    }

    /**
     * Options grouped by name, in display order: ["Color" => [...], ...].
     *
     * @return array<string, list<ProductOption>>
     */
    public function optionGroups(): array
    {
        $groups = [];
        foreach ($this->options as $option) {
            $groups[$option->group][] = $option;
        }

        return $groups;
    }

    /** Whether any option costs extra, so the price shown is a "from" price. */
    public function hasPriceRange(): bool
    {
        foreach ($this->options as $option) {
            if ($option->extraCents > 0) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Catalog\CatalogQuery;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductRepository;
use TechNest\Tests\Support\DatabaseTestCase;

final class ProductRepositoryTest extends DatabaseTestCase
{
    private ProductRepository $products;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = new ProductRepository($this->db, $this->clock);

        $this->fixtures->product(['name' => 'Galaxy Phone', 'slug' => 'galaxy', 'price' => 90000, 'category' => 'phones', 'featured' => true]);
        $this->clock->advance('+1 day');
        $this->fixtures->product(['name' => 'Studio Headphones', 'slug' => 'studio', 'price' => 30000, 'category' => 'audio', 'stock' => 0]);
        $this->clock->advance('+1 day');
        $this->fixtures->product(['name' => 'Pocket Speaker', 'slug' => 'pocket', 'price' => 5000, 'category' => 'audio', 'description' => 'Loud and 100% waterproof']);
        $this->fixtures->product(['name' => 'Retired Phone', 'slug' => 'retired', 'category' => 'phones', 'active' => false]);
    }

    public function testSearchMatchesNameDescriptionAndCategoryCaseInsensitively(): void
    {
        self::assertSame(['Galaxy Phone'], $this->names(new CatalogQuery(search: 'GALAXY')));
        self::assertSame(['Pocket Speaker'], $this->names(new CatalogQuery(search: 'waterproof')));
        self::assertSame(['Studio Headphones', 'Pocket Speaker'], $this->names(new CatalogQuery(search: 'audio')));
    }

    public function testLikeWildcardsInSearchesAreLiteral(): void
    {
        self::assertSame(['Pocket Speaker'], $this->names(new CatalogQuery(search: '100%')));
        // Unescaped, "%" would match all three products; escaped, only the
        // one whose description contains a literal percent sign.
        self::assertSame(['Pocket Speaker'], $this->names(new CatalogQuery(search: '%')));
        self::assertSame([], $this->names(new CatalogQuery(search: '_')));
    }

    public function testArchivedProductsNeverAppearInTheStore(): void
    {
        self::assertNotContains('Retired Phone', $this->names(new CatalogQuery()));
        self::assertNull($this->products->findBySlug('retired'));
        self::assertNotNull($this->products->findBySlug('retired', includeArchived: true));
    }

    public function testFiltersByCategoryAndStock(): void
    {
        self::assertSame(['Studio Headphones', 'Pocket Speaker'], $this->names(new CatalogQuery(category: 'audio')));
        self::assertSame(['Pocket Speaker'], $this->names(new CatalogQuery(category: 'audio', inStockOnly: true)));
    }

    public function testSorting(): void
    {
        self::assertSame(['Pocket Speaker', 'Studio Headphones', 'Galaxy Phone'], $this->names(new CatalogQuery(sort: 'price-asc')));
        self::assertSame(['Galaxy Phone', 'Studio Headphones', 'Pocket Speaker'], $this->names(new CatalogQuery(sort: 'price-desc')));
        self::assertSame(['Pocket Speaker', 'Studio Headphones', 'Galaxy Phone'], $this->names(new CatalogQuery(sort: 'newest')));
        self::assertSame('Galaxy Phone', $this->names(new CatalogQuery(sort: 'featured'))[0]);
    }

    public function testPaginationReportsTotalsAcrossPages(): void
    {
        $page = $this->products->search(new CatalogQuery(sort: 'name', page: 2, perPage: 2));

        self::assertSame(3, $page->total);
        self::assertSame(2, $page->lastPage());
        self::assertSame(['Studio Headphones'], array_map(static fn(Product $p): string => $p->name, $page->items));
        self::assertSame(3, $page->firstItem());
    }

    public function testOptionsAreLoadedInOneQueryForAWholePage(): void
    {
        $this->fixtures->product(['name' => 'Configurable', 'options' => ['Size' => [['S', 0], ['L', 500]]]]);
        $result = $this->products->search(new CatalogQuery(search: 'Configurable'));

        self::assertSame(['S', 'L'], array_map(static fn($o): string => $o->value, $result->items[0]->options));
        self::assertTrue($result->items[0]->hasPriceRange());
    }

    public function testFindManyIncludesArchivedProductsForCartReconciliation(): void
    {
        $retired = $this->products->findBySlug('retired', includeArchived: true);
        self::assertNotNull($retired);

        $found = $this->products->findMany([$retired->id, 99999]);

        self::assertSame([$retired->id], array_keys($found));
        self::assertFalse($found[$retired->id]->isActive);
    }

    public function testSlugUniquenessIgnoresTheProductBeingEdited(): void
    {
        $galaxy = $this->products->findBySlug('galaxy');
        self::assertNotNull($galaxy);

        self::assertTrue($this->products->slugExists('galaxy'));
        self::assertFalse($this->products->slugExists('galaxy', $galaxy->id));
    }

    /** @return list<string> */
    private function names(CatalogQuery $query): array
    {
        return array_map(static fn(Product $p): string => $p->name, $this->products->search($query)->items);
    }
}

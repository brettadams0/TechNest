<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\PriceCalculator;
use TechNest\Database\Migrator;
use TechNest\Database\Row;
use TechNest\Database\Seeder;
use TechNest\Order\OrderRepository;
use TechNest\Tests\Support\DatabaseTestCase;
use TechNest\Tests\Support\TestDatabase;
use TechNest\User\UserRepository;

final class SeederTest extends DatabaseTestCase
{
    private Seeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seeder = new Seeder($this->db, $this->clock, TestDatabase::root() . '/database/seed/catalog.php');
    }

    public function testSeedsTheCatalogWithAValidOptionSetForEveryProduct(): void
    {
        self::assertFalse($this->seeder->hasCatalog());
        self::assertSame(20, $this->seeder->seedCatalog());
        self::assertTrue($this->seeder->hasCatalog());

        self::assertSame(9, $this->fixtures->count('categories'));
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM themes WHERE is_active = 1'));
        // Every product image the seed references exists on disk.
        foreach ($this->db->fetchAll('SELECT image FROM products') as $row) {
            self::assertFileExists(TestDatabase::root() . '/public/assets/img/products/' . Row::string($row, 'image'));
        }
    }

    public function testDemoDataIsInternallyConsistent(): void
    {
        $this->seeder->seedCatalog();
        $orders = $this->seeder->seedDemo(
            new UserRepository($this->db, $this->clock),
            new ProductRepository($this->db, $this->clock),
            new OrderRepository($this->db),
            new PriceCalculator(),
        );

        self::assertGreaterThan(50, $orders);

        // Every order's lines add up to its subtotal, and its total adds up.
        self::assertSame(0, $this->db->fetchInt(
            'SELECT COUNT(*) FROM orders o
              WHERE o.subtotal_cents <> (SELECT SUM(i.line_total_cents) FROM order_items i WHERE i.order_id = o.id)
                 OR o.total_cents <> o.subtotal_cents + o.shipping_cents + o.tax_cents',
        ));
        // Every order's history ends in its current status.
        self::assertSame(0, $this->db->fetchInt(
            'SELECT COUNT(*) FROM orders o
              WHERE o.status <> (SELECT h.to_status FROM order_status_history h WHERE h.order_id = o.id ORDER BY h.changed_at DESC, h.id DESC LIMIT 1)',
        ));
        // Nothing is dated in the future.
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM orders WHERE created_at > ?', ['2026-03-10 15:00:00']));

        $admin = (new UserRepository($this->db, $this->clock))->findByUsername(Seeder::DEMO_ADMIN[0]);
        self::assertNotNull($admin);
        self::assertTrue((new UserRepository($this->db, $this->clock))->isAdmin($admin->id));
    }

    public function testMigrationsAreIdempotent(): void
    {
        $migrator = new Migrator($this->db, TestDatabase::root() . '/database/migrations', $this->clock);

        self::assertSame([], $migrator->migrate());
    }
}

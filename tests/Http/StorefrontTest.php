<?php

declare(strict_types=1);

namespace TechNest\Tests\Http;

use TechNest\Application;
use TechNest\Config;
use TechNest\Database\Database;
use TechNest\Http\Request;
use TechNest\Security\ArraySession;
use TechNest\Tests\Support\HttpTestCase;
use TechNest\Tests\Support\TestDatabase;

final class StorefrontTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures->phone();
        $this->fixtures->product(['name' => 'Travel Speaker', 'slug' => 'travel-speaker', 'category' => 'audio', 'featured' => true]);
        $this->fixtures->product(['name' => 'Hidden Gadget', 'slug' => 'hidden-gadget', 'active' => false]);
    }

    public function testPublicPagesRender(): void
    {
        foreach (['/', '/catalog', '/catalog?category=audio&sort=price-desc&in_stock=1', '/products/pixel-test', '/cart', '/help', '/login', '/register'] as $uri) {
            $this->assertStatus(200, $this->get($uri));
        }
    }

    public function testCatalogSearchAndFilters(): void
    {
        $response = $this->get('/catalog?q=speaker');

        $this->assertSee('Results for “speaker”', $response);
        $this->assertSee('Travel Speaker', $response);
        $this->assertDontSee('Pixel Test', $response);
    }

    public function testAPagePastTheEndRedirectsToTheLastOne(): void
    {
        // A stale link, e.g. after products were archived.
        $this->assertRedirect('/catalog?category=audio', $this->get('/catalog?category=audio&page=5'));
    }

    public function testArchivedProductsAre404(): void
    {
        $this->assertStatus(404, $this->get('/products/hidden-gadget'));
        $this->assertDontSee('Hidden Gadget', $this->get('/catalog'));
    }

    public function testUnknownPagesAndCategoriesAre404(): void
    {
        $this->assertStatus(404, $this->get('/products/does-not-exist'));
        $this->assertStatus(404, $this->get('/catalog?category=nope'));
        $this->assertStatus(404, $this->get('/wp-admin'));
    }

    public function testWrongMethodIs405(): void
    {
        $response = $this->get('/cart/add');

        $this->assertStatus(405, $response);
        self::assertSame('POST', $response->header('Allow'));
    }

    public function testHealthCheckReportsStatusWithoutLeakingVersions(): void
    {
        $response = $this->get('/health');

        $this->assertStatus(200, $response);
        self::assertSame('application/json', $response->header('Content-Type'));
        self::assertSame(['status' => 'ok', 'database' => 'ok'], json_decode($response->body, true));
        self::assertStringNotContainsString(PHP_VERSION, $response->body);
    }

    public function testHealthReportsADatabaseThatWasNeverMigrated(): void
    {
        // Regression: /health ran SELECT 1, so an empty database reported ok.
        $app = new Application(TestDatabase::root(), Config::fromArray(['APP_ENV' => 'testing']), Database::sqlite(), new ArraySession(), $this->clock);

        $response = $app->handle(new Request('GET', '/health'));

        $this->assertStatus(503, $response);
        self::assertSame(['status' => 'degraded', 'database' => 'not set up'], json_decode($response->body, true));
    }

    public function testProductPageOffersEveryOptionWithTheFirstSelected(): void
    {
        $response = $this->get('/products/pixel-test');

        $this->assertSee('Storage', $response);
        $this->assertSee('256GB', $response);
        self::assertSame(2, substr_count($response->body, ' checked>'), 'One default per option group');
        self::assertStringContainsString('data-base-price="99900"', $response->body);
    }

    public function testTheActiveThemeIsAppliedToEveryPage(): void
    {
        $this->db->execute("INSERT INTO themes (slug, name, is_active) VALUES ('regular', 'Regular', 0), ('holiday', 'Holiday', 1)");

        self::assertStringContainsString('data-theme="holiday"', $this->get('/')->body);
    }
}

<?php

declare(strict_types=1);

namespace TechNest\Tests\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use TechNest\Http\Route;
use TechNest\Tests\Support\HttpTestCase;

/**
 * Security properties checked across the whole route table, so a route added
 * later is covered without anyone remembering to add a test for it.
 */
final class SecurityTest extends HttpTestCase
{
    public function testEveryAdminPathIsBehindTheAdminGuard(): void
    {
        foreach ($this->app->router->routes() as $route) {
            if (str_starts_with($route->pattern, '/admin')) {
                self::assertSame(Route::GUARD_ADMIN, $route->guard, "{$route->method} {$route->pattern} must be admin-only");
            }
            if (str_starts_with($route->pattern, '/account') || str_starts_with($route->pattern, '/checkout')) {
                self::assertSame(Route::GUARD_AUTH, $route->guard, "{$route->method} {$route->pattern} must require sign-in");
            }
        }
    }

    public function testGuestsAreSentToSignInFromEveryAdminRoute(): void
    {
        $this->seedAdminTargets();
        foreach ($this->adminRoutes() as [$method, $path]) {
            $response = $method === 'GET' ? $this->get($path) : $this->post($path);
            $this->assertRedirect('/login', $response);
        }
    }

    public function testCustomersGet403FromEveryAdminRoute(): void
    {
        $this->seedAdminTargets();
        $this->actingAs($this->fixtures->user('customer'));

        foreach ($this->adminRoutes() as [$method, $path]) {
            $response = $method === 'GET' ? $this->get($path) : $this->post($path);
            $this->assertStatus(403, $response);
        }

        self::assertSame(1, $this->db->fetchInt('SELECT is_active FROM products WHERE id = 1'), 'Nothing was changed');
    }

    public function testAdminsCanReachEveryAdminPage(): void
    {
        $this->seedAdminTargets();
        $this->actingAs($this->fixtures->user('boss', admin: true));

        foreach ($this->adminRoutes() as [$method, $path]) {
            if ($method === 'GET') {
                $this->assertStatus(200, $this->get($path));
            }
        }
    }

    #[DataProvider('csrfCases')]
    public function testEveryPostIsRejectedWithoutAValidCsrfToken(?string $token): void
    {
        $this->actingAs($this->fixtures->user('mallory', admin: true));
        $product = $this->fixtures->product();

        foreach ($this->app->router->routes() as $route) {
            if ($route->method !== 'POST') {
                continue;
            }
            $path = (string) preg_replace('/\{[^}]+\}/', (string) $product->id, $route->pattern);
            $data = $token === null ? [] : ['_token' => $token];
            $response = $this->post($path, $data, withCsrfToken: false);

            $this->assertStatus(419, $response);
        }

        self::assertSame(1, $this->db->fetchInt('SELECT is_active FROM products WHERE id = ?', [$product->id]));
    }

    /** @return iterable<string, array{?string}> */
    public static function csrfCases(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'forged' => [str_repeat('a', 64)];
    }

    public function testSecurityHeadersAreOnEveryResponseIncludingErrors(): void
    {
        foreach (['/', '/nope', '/admin'] as $uri) {
            $response = $this->get($uri);

            self::assertStringContainsString("script-src 'self'", (string) $response->header('Content-Security-Policy'));
            self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
            self::assertSame('DENY', $response->header('X-Frame-Options'));
            self::assertSame('no-store, private', $response->header('Cache-Control'));
        }
    }

    public function testRenderedPagesNeedNothingTheCspForbids(): void
    {
        $this->seedAdminTargets();
        $pages = ['/', '/catalog', '/products/product-1', '/cart', '/help', '/login', '/register', '/nope'];
        foreach ($pages as $uri) {
            $this->assertCspClean($uri);
        }

        $this->actingAs($this->fixtures->user('csp', admin: true));
        foreach ([...$pages, '/account', '/admin', '/admin/orders', '/admin/orders/1', '/admin/products', '/admin/products/1/edit', '/admin/products/new', '/admin/users', '/admin/themes'] as $uri) {
            $this->assertCspClean($uri);
        }
    }

    public function testUserContentIsEscapedEverywhereItAppears(): void
    {
        $payload = '<script>alert(1)</script>';
        $product = $this->fixtures->product(['name' => $payload, 'slug' => 'xss', 'description' => '"><img src=x onerror=alert(1)>']);
        $this->actingAs($this->fixtures->user('admin1', admin: true));
        $this->post('/cart/add', ['product_id' => (string) $product->id]);

        foreach (['/catalog', '/catalog?q=' . urlencode($payload), '/products/xss', '/cart', '/admin/products', '/admin/products/' . $product->id . '/edit'] as $uri) {
            $body = $this->get($uri)->body;
            self::assertStringNotContainsString($payload, $body, $uri);
            self::assertStringNotContainsString('<img src=x', $body, $uri);
        }
    }

    private function assertCspClean(string $uri): void
    {
        $body = $this->get($uri)->body;

        self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $body, "{$uri} uses an inline style attribute");
        self::assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $body, "{$uri} has an inline <script>");
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $body, "{$uri} has an inline event handler");
        self::assertDoesNotMatchRegularExpression('/(?:src|href)="https?:\/\/(?!github\.com|brettadams0\.github\.io)/i', $body, "{$uri} loads a third-party resource");
    }

    /** @return list<array{string, string}> every admin route with ids filled in */
    private function adminRoutes(): array
    {
        $routes = [];
        foreach ($this->app->router->routes() as $route) {
            if ($route->guard === Route::GUARD_ADMIN) {
                $routes[] = [$route->method, (string) preg_replace('/\{[^}]+\}/', '1', $route->pattern)];
            }
        }
        self::assertGreaterThan(15, count($routes));

        return $routes;
    }

    /** Rows with id 1 for every parameterised admin route to act on. */
    private function seedAdminTargets(): void
    {
        $product = $this->fixtures->product(['options' => ['Color' => [['Red', 0]]]]);
        $owner = $this->fixtures->user('target');
        $this->db->execute(
            "INSERT INTO orders (user_id, status, subtotal_cents, shipping_cents, tax_cents, total_cents, ship_name, ship_street, ship_city, ship_province, ship_postal_code, created_at, updated_at)
             VALUES (?, 'pending', 100, 0, 13, 113, 'T', '1 St', 'City', 'ON', 'N9B 3P4', '2026-03-01 00:00:00', '2026-03-01 00:00:00')",
            [$owner->id],
        );
        $this->db->execute(
            "INSERT INTO order_items (order_id, product_id, product_name, options_label, unit_price_cents, quantity, line_total_cents) VALUES (1, ?, 'P', '', 100, 1, 100)",
            [$product->id],
        );
    }
}

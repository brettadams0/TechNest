<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Http\HttpException;
use TechNest\Http\Route;
use TechNest\Http\Router;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', [self::class, 'home']);
        $this->router->get('/products/{slug:[a-z0-9-]+}', [self::class, 'product']);
        $this->router->post('/orders/{id:\d+}/status', [self::class, 'status'], Route::GUARD_ADMIN);
        $this->router->group(Route::GUARD_AUTH, static function (Router $r): void {
            $r->get('/account', [self::class, 'account']);
        });
        $this->router->get('/after-group', [self::class, 'after']);
    }

    public function testMatchesStaticAndParameterisedRoutes(): void
    {
        [$route, $params] = $this->router->match('GET', '/products/iphone-15-pro');

        self::assertSame('/products/{slug:[a-z0-9-]+}', $route->pattern);
        self::assertSame(['slug' => 'iphone-15-pro'], $params);
    }

    public function testParameterConstraintsAreEnforced(): void
    {
        $this->expectExceptionObject(HttpException::notFound());
        $this->router->match('POST', '/orders/12abc/status');
    }

    public function testUnknownPathIs404(): void
    {
        try {
            $this->router->match('GET', '/nope');
            self::fail('Expected a 404');
        } catch (HttpException $e) {
            self::assertSame(404, $e->status);
        }
    }

    public function testWrongMethodIs405WithAllowHeader(): void
    {
        try {
            $this->router->match('GET', '/orders/5/status');
            self::fail('Expected a 405');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status);
            self::assertSame(['Allow' => 'POST'], $e->headers);
        }
    }

    public function testHeadIsServedByGetRoutes(): void
    {
        [$route] = $this->router->match('HEAD', '/');

        self::assertSame('GET', $route->method);
    }

    public function testGroupsApplyTheirGuardAndThenRestoreTheDefault(): void
    {
        self::assertSame(Route::GUARD_AUTH, $this->router->match('GET', '/account')[0]->guard);
        self::assertSame(Route::GUARD_NONE, $this->router->match('GET', '/after-group')[0]->guard);
        self::assertSame(Route::GUARD_ADMIN, $this->router->match('POST', '/orders/1/status')[0]->guard);
    }
}

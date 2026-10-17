<?php

declare(strict_types=1);

namespace TechNest\Http;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    private string $guard = Route::GUARD_NONE;

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $pattern, array $handler, ?string $guard = null): void
    {
        $this->routes[] = new Route('GET', $pattern, $handler, $guard ?? $this->guard);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $pattern, array $handler, ?string $guard = null): void
    {
        $this->routes[] = new Route('POST', $pattern, $handler, $guard ?? $this->guard);
    }

    /**
     * Register a block of routes that all share one guard.
     *
     * @param callable(self): void $routes
     */
    public function group(string $guard, callable $routes): void
    {
        $previous = $this->guard;
        $this->guard = $guard;
        try {
            $routes($this);
        } finally {
            $this->guard = $previous;
        }
    }

    /**
     * @return array{0: Route, 1: array<string, string>}
     *
     * @throws HttpException 404 when nothing matches the path, 405 when the
     *                       path exists but not for this method
     */
    public function match(string $method, string $path): array
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        $allowed = [];

        foreach ($this->routes as $route) {
            $params = $route->match($path);
            if ($params === null) {
                continue;
            }
            if ($route->method === $method) {
                return [$route, $params];
            }
            $allowed[] = $route->method;
        }

        if ($allowed !== []) {
            throw new HttpException(405, '', ['Allow' => implode(', ', array_unique($allowed))]);
        }

        throw HttpException::notFound();
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }
}

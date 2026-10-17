<?php

declare(strict_types=1);

namespace TechNest;

use RuntimeException;

/**
 * A deliberately tiny service container: named factories, each run at most
 * once. Wiring is explicit in Application::services() — no reflection and no
 * autowiring, so following a dependency is a matter of reading one method.
 */
final class Container
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @template T of object
     *
     * @param class-string<T>     $id
     * @param callable(self): T   $factory
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            $factory = $this->factories[$id] ?? throw new RuntimeException("No service registered for {$id}");
            $this->instances[$id] = $factory($this);
        }

        $instance = $this->instances[$id];
        if (!$instance instanceof $id) {
            throw new RuntimeException("Service {$id} has the wrong type");
        }

        return $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}

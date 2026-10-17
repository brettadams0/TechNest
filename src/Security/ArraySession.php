<?php

declare(strict_types=1);

namespace TechNest\Security;

/** In-memory session for tests and the CLI. */
final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];

    public int $regenerations = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    public function regenerate(): void
    {
        ++$this->regenerations;
    }

    public function invalidate(): void
    {
        $this->data = [];
        ++$this->regenerations;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }
}

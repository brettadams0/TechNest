<?php

declare(strict_types=1);

namespace TechNest\User;

use TechNest\Database\Row;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly int $sessionVersion = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            Row::int($row, 'id'),
            Row::string($row, 'username'),
            Row::string($row, 'email'),
            Row::bool($row, 'is_active'),
            Row::string($row, 'created_at'),
            isset($row['session_version']) ? Row::int($row, 'session_version') : 0,
        );
    }
}

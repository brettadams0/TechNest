<?php

declare(strict_types=1);

namespace TechNest\Support;

use DateTimeImmutable;
use DateTimeZone;

final class FrozenClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(string $at = '2026-01-15 12:00:00')
    {
        $this->now = new DateTimeImmutable($at, new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}

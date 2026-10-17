<?php

declare(strict_types=1);

namespace TechNest\Support;

use DateTimeImmutable;

/**
 * The current time, injected rather than read from the system so that
 * anything time-dependent (login throttling, order timestamps, reports)
 * can be tested at a fixed instant.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}

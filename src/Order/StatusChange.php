<?php

declare(strict_types=1);

namespace TechNest\Order;

final class StatusChange
{
    public function __construct(
        public readonly ?OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly ?string $changedBy,
        public readonly string $changedAt,
    ) {}
}

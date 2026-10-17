<?php

declare(strict_types=1);

namespace TechNest\Tests\Support;

use PHPUnit\Framework\TestCase;
use TechNest\Database\Database;
use TechNest\Support\FrozenClock;

abstract class DatabaseTestCase extends TestCase
{
    protected Database $db;
    protected FrozenClock $clock;
    protected Fixtures $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FrozenClock('2026-03-10 15:00:00');
        $this->db = TestDatabase::create($this->clock);
        $this->fixtures = new Fixtures($this->db, $this->clock);
    }
}

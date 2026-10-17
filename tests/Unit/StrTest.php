<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Support\Str;

final class StrTest extends TestCase
{
    public function testLimitCountsCharactersNotBytes(): void
    {
        // Regression: cutting by bytes split the "é" and left invalid UTF-8
        // in the page's meta description.
        $limited = Str::limit(str_repeat('a', 153) . 'é et plus encore', 155);

        self::assertTrue(mb_check_encoding($limited, 'UTF-8'));
        self::assertSame(155, mb_strlen($limited));
        self::assertStringEndsWith('aé…', $limited);
        self::assertSame('Court', Str::limit('Court', 155));
    }

    public function testSlug(): void
    {
        self::assertSame('pixel-9-pro', Str::slug('  Pixel 9 Pro!  '));
    }
}

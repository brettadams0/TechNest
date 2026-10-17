<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Admin\Chart;

final class ChartTest extends TestCase
{
    #[DataProvider('maxima')]
    public function testAxisMaximaRoundUpToReadableNumbers(int $value, int $expected): void
    {
        self::assertSame($expected, Chart::niceMax($value));
    }

    /** @return iterable<array{int, int}> */
    public static function maxima(): iterable
    {
        yield [0, 1];
        yield [7, 10];
        yield [12, 20];
        yield [230, 250];
        yield [480, 500];
        yield [4_620_00, 5_000_00];
        yield [100, 100];
    }

    public function testColumnsDrawOneBarPerPointWithAnEscapedTooltip(): void
    {
        $svg = Chart::columns(
            [
                ['label' => 'Mon', 'value' => 100, 'title' => 'Monday <b>'],
                ['label' => 'Tue', 'value' => 0, 'title' => 'Tuesday'],
                ['label' => 'Wed', 'value' => 50, 'title' => 'Wednesday'],
            ],
            static fn(int $v): string => (string) $v,
            'Revenue',
        );

        self::assertSame(3, substr_count($svg, 'class="chart__bar"'));
        self::assertStringContainsString('<title>Monday &lt;b&gt;</title>', $svg);
        self::assertStringContainsString('aria-label="Revenue"', $svg);
        self::assertStringNotContainsString('style=', $svg, 'Inline styles would be blocked by the CSP');
    }

    public function testBarsAreSizedRelativeToTheLargestValue(): void
    {
        $html = Chart::bars([
            ['label' => 'Audio', 'value' => 200, 'display' => '$2'],
            ['label' => 'Phones', 'value' => 50, 'display' => '$0.50'],
        ], 'By category');

        self::assertStringContainsString('width="100.0%"', $html);
        self::assertStringContainsString('width="25.0%"', $html);
    }
}

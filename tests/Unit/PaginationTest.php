<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Catalog\Paginated;
use TechNest\Http\Request;

final class PaginationTest extends TestCase
{
    /** @param list<int|null> $expected */
    #[DataProvider('windows')]
    public function testLinksTheEndsAndTheNeighbourhoodOfTheCurrentPage(int $pages, int $current, array $expected): void
    {
        $paginated = new Paginated([], $pages * 10, $current, 10);

        self::assertSame($expected, $paginated->window());
    }

    /** @return iterable<string, array{int, int, list<int|null>}> */
    public static function windows(): iterable
    {
        yield 'few pages' => [4, 2, [1, 2, 3, 4]];
        yield 'at the start' => [33, 1, [1, 2, 3, null, 33]];
        yield 'in the middle' => [33, 17, [1, null, 15, 16, 17, 18, 19, null, 33]];
        yield 'at the end' => [33, 33, [1, null, 31, 32, 33]];
        // A gap of one page is the page itself, not an ellipsis.
        yield 'near the start' => [33, 5, [1, 2, 3, 4, 5, 6, 7, null, 33]];
    }

    #[DataProvider('pageParameters')]
    public function testPageNumbersAreBounded(?string $raw, int $expected): void
    {
        $request = new Request('GET', '/', $raw === null ? [] : ['page' => $raw], [], []);

        self::assertSame($expected, $request->queryPage());
    }

    /** @return iterable<string, array{?string, int}> */
    public static function pageParameters(): iterable
    {
        yield 'missing' => [null, 1];
        yield 'normal' => ['7', 7];
        yield 'zero' => ['0', 1];
        yield 'negative' => ['-3', 1];
        yield 'not a number' => ['two', 1];
        yield 'past the cap' => ['5000', 1000];
        // Regression: this overflowed OFFSET into a float and became a 500.
        yield 'absurd' => ['99999999999999999999', 1000];
    }
}

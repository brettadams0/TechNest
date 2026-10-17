<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Security\SafeRedirect;

final class SafeRedirectTest extends TestCase
{
    #[DataProvider('targets')]
    public function testOnlyLocalPathsAreFollowed(?string $target, string $expected): void
    {
        self::assertSame($expected, SafeRedirect::localPath($target, '/account'));
    }

    /** @return iterable<string, array{?string, string}> */
    public static function targets(): iterable
    {
        yield 'local path' => ['/checkout', '/checkout'];
        yield 'local path with query' => ['/catalog?category=audio', '/catalog?category=audio'];
        yield 'null' => [null, '/account'];
        yield 'empty' => ['', '/account'];
        yield 'absolute URL' => ['https://evil.example/login', '/account'];
        yield 'protocol-relative' => ['//evil.example', '/account'];
        yield 'backslash trick' => ['/\\evil.example', '/account'];
        yield 'javascript scheme' => ['javascript:alert(1)', '/account'];
        yield 'header injection' => ["/account\r\nSet-Cookie: x=1", '/account'];
        yield 'relative path' => ['checkout', '/account'];
    }
}

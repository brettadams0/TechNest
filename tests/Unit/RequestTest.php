<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TechNest\Http\IpRange;
use TechNest\Http\Request;

final class RequestTest extends TestCase
{
    private const PROXIES = ['10.0.0.0/8', '2001:db8::/32'];

    public function testForwardedHeadersAreIgnoredWithoutATrustedProxy(): void
    {
        $request = $this->request(['REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_FORWARDED_FOR' => '198.51.100.1', 'HTTP_X_FORWARDED_PROTO' => 'https'], []);

        self::assertSame('203.0.113.9', $request->ip());
        self::assertFalse($request->isSecure());
    }

    public function testForwardedHeadersFromAnUntrustedAddressAreIgnored(): void
    {
        $request = $this->request(['REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_FORWARDED_FOR' => '198.51.100.1', 'HTTP_X_FORWARDED_PROTO' => 'https']);

        self::assertSame('203.0.113.9', $request->ip());
        self::assertFalse($request->isSecure());
    }

    public function testBehindATrustedProxyTheClientIsTheLastUntrustedHop(): void
    {
        // The client wrote "1.2.3.4" itself; only the hops our proxies added count.
        $request = $this->request([
            'REMOTE_ADDR' => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 198.51.100.7, 10.0.0.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        self::assertSame('198.51.100.7', $request->ip());
        self::assertTrue($request->isSecure());
    }

    public function testAMalformedForwardedForFallsBackToThePeer(): void
    {
        $request = $this->request(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => 'not-an-ip']);

        self::assertSame('10.0.0.5', $request->ip());
    }

    #[DataProvider('ranges')]
    public function testCidrMatching(string $ip, string $range, bool $expected): void
    {
        self::assertSame($expected, IpRange::matches($ip, $range));
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function ranges(): iterable
    {
        yield 'inside /8' => ['10.200.3.4', '10.0.0.0/8', true];
        yield 'outside /8' => ['11.0.0.1', '10.0.0.0/8', false];
        yield 'partial byte /20 inside' => ['172.16.15.255', '172.16.0.0/20', true];
        yield 'partial byte /20 outside' => ['172.16.16.0', '172.16.0.0/20', false];
        yield 'single address' => ['192.0.2.1', '192.0.2.1', true];
        yield 'ipv6 inside' => ['2001:db8::1', '2001:db8::/32', true];
        yield 'ipv4 against ipv6' => ['10.0.0.1', '2001:db8::/32', false];
        yield 'bad prefix' => ['10.0.0.1', '10.0.0.0/40', false];
        yield 'garbage' => ['10.0.0.1', 'proxy.internal', false];
    }

    /**
     * @param array<string, string> $server
     * @param list<string>          $proxies
     */
    private function request(array $server, array $proxies = self::PROXIES): Request
    {
        return new Request('GET', '/', [], [], $server, $proxies);
    }
}

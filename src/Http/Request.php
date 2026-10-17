<?php

declare(strict_types=1);

namespace TechNest\Http;

/**
 * An immutable view of the incoming request. Controllers read input through
 * this instead of touching $_GET / $_POST / $_SERVER, which is what lets the
 * HTTP tests drive the whole application in-process.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, string> $server
     * @param list<string>          $trustedProxies addresses or CIDR blocks of
     *                                              reverse proxies whose
     *                                              X-Forwarded-* headers are
     *                                              believed
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $server = [],
        public readonly array $trustedProxies = [],
    ) {}

    /** @param list<string> $trustedProxies */
    public static function fromGlobals(array $trustedProxies = []): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);

        $server = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($value)) {
                $server[(string) $key] = $value;
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return new self(
            strtoupper(is_string($method) ? $method : 'GET'),
            self::normalisePath(is_string($path) ? $path : '/'),
            self::stringKeys($_GET),
            self::stringKeys($_POST),
            $server,
            $trustedProxies,
        );
    }

    /**
     * @param array<mixed> $input
     *
     * @return array<string, mixed>
     */
    private static function stringKeys(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }

    public static function normalisePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function isGet(): bool
    {
        return $this->method === 'GET' || $this->method === 'HEAD';
    }

    /** A single scalar query-string value, trimmed. Arrays are ignored. */
    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? null;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** A single scalar form value. Not trimmed, so passwords survive intact. */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function inputInt(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return preg_match('/^-?\d{1,9}$/', trim($value)) === 1 ? (int) $value : $default;
    }

    /**
     * A page number from the query string, 1 to $max. Anything else (missing,
     * negative, not a number, absurdly large) is page 1 or $max, never a
     * number that would overflow an OFFSET.
     */
    public function queryPage(int $max = 1000): int
    {
        $page = $this->query('page', '1');
        if (preg_match('/^\d+$/', $page) !== 1) {
            return 1;
        }

        return strlen(ltrim($page, '0')) > 6 ? $max : max(1, min((int) $page, $max));
    }

    /** A 0/1 form field as a bool, or null when it is missing or anything else. */
    public function inputFlag(string $key): ?bool
    {
        return match ($this->input($key)) {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    /**
     * An array of integers from the form, e.g. options[Color]=12. Anything
     * that is not a plain integer is dropped rather than coerced.
     *
     * @return array<string, int>
     */
    public function inputIntMap(string $key): array
    {
        $value = $this->body[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $k => $v) {
            if (is_scalar($v) && preg_match('/^\d{1,9}$/', (string) $v) === 1) {
                $out[(string) $k] = (int) $v;
            }
        }

        return $out;
    }

    /**
     * The client's address. Behind a trusted reverse proxy that is the last
     * address in X-Forwarded-For that is not one of our own proxies; anything
     * further left was written by the client and proves nothing. Without a
     * trusted proxy the header is ignored entirely, since anyone can send it.
     */
    public function ip(): string
    {
        $remote = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->fromTrustedProxy()) {
            return $remote;
        }

        $hops = array_reverse(array_map('trim', explode(',', $this->server['HTTP_X_FORWARDED_FOR'] ?? '')));
        foreach ($hops as $hop) {
            if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                break;
            }
            if (!IpRange::matchesAny($hop, $this->trustedProxies)) {
                return $hop;
            }
        }

        return $remote;
    }

    /** HTTPS, either here or, behind a trusted proxy, at the proxy. */
    public function isSecure(): bool
    {
        if ($this->fromTrustedProxy() && isset($this->server['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower(trim(explode(',', $this->server['HTTP_X_FORWARDED_PROTO'])[0])) === 'https';
        }

        $https = $this->server['HTTPS'] ?? '';

        return ($https !== '' && $https !== 'off') || ($this->server['SERVER_PORT'] ?? '') === '443';
    }

    private function fromTrustedProxy(): bool
    {
        return $this->trustedProxies !== [] && IpRange::matchesAny($this->server['REMOTE_ADDR'] ?? '', $this->trustedProxies);
    }

    /** Path plus query string, for "send me back here after login". */
    public function fullPath(): string
    {
        return $this->query === [] ? $this->path : $this->path . '?' . http_build_query($this->query);
    }
}

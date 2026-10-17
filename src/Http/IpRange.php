<?php

declare(strict_types=1);

namespace TechNest\Http;

/** IPv4/IPv6 address and CIDR matching, for the trusted-proxy list. */
final class IpRange
{
    /** @param list<string> $ranges addresses or CIDR blocks, e.g. 10.0.0.0/8 */
    public static function matchesAny(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (self::matches($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    public static function matches(string $ip, string $range): bool
    {
        $slash = strpos($range, '/');
        $subnet = $slash === false ? $range : substr($range, 0, $slash);
        $bits = $slash === false ? null : substr($range, $slash + 1);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false || filter_var($subnet, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $address = (string) inet_pton($ip);
        $network = (string) inet_pton($subnet);
        if (strlen($address) !== strlen($network)) {
            return false; // IPv4 against an IPv6 range, or the reverse
        }

        $size = strlen($address) * 8;
        $prefix = $bits === null ? $size : (ctype_digit($bits) ? (int) $bits : -1);
        if ($prefix < 0 || $prefix > $size) {
            return false;
        }

        $whole = intdiv($prefix, 8);
        if (substr($address, 0, $whole) !== substr($network, 0, $whole)) {
            return false;
        }
        $rest = $prefix % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($address[$whole]) & $mask) === (ord($network[$whole]) & $mask);
    }
}

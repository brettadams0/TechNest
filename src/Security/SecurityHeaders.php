<?php

declare(strict_types=1);

namespace TechNest\Security;

use TechNest\Http\Response;

/**
 * Headers applied to every response. The Content-Security-Policy allows
 * scripts and styles from this origin only — no inline <script>, no style=""
 * attributes — which the template test suite enforces page by page, so an
 * injected payload has nothing it is allowed to execute.
 */
final class SecurityHeaders
{
    public const CSP = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
        . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; "
        . "base-uri 'none'; object-src 'none'";

    public static function apply(Response $response, bool $https): Response
    {
        $headers = [
            'Content-Security-Policy' => self::CSP,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if ($https) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}

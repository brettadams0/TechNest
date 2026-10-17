<?php

declare(strict_types=1);

namespace TechNest\View;

/**
 * Inline stroke icons on a 24×24 grid. Inline SVG needs no request, no icon
 * font and nothing the Content-Security-Policy has to allow.
 */
final class Icon
{
    private const PATHS = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart' => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.5 3 7.7 7 9 4-1.3 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'package' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c1.8.8 3 2.6 3.5 5.2"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.2 0 2-.8 2-1.8 0-.5-.2-.9-.5-1.3-.3-.3-.5-.8-.5-1.2 0-1 .8-1.7 1.8-1.7H17a4 4 0 0 0 4-4C21 6.5 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="14.5" cy="7" r="1"/>',
        'store' => '<path d="M4 10v10h16V10M3 6l2-3h14l2 3v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M10 20v-5h4v5"/>',
        'logout' => '<path d="M15 4h4v16h-4M10 17l5-5-5-5M15 12H3"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'alert' => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17.5v.5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'archive' => '<path d="M3 4h18v4H3zM5 8v12h14V8M10 12h4"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
        'github' => '<path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/>',
    ];

    public static function svg(string $name, string $class = 'icon'): string
    {
        $paths = self::PATHS[$name] ?? self::PATHS['info'];

        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $paths . '</svg>';
    }

    /** The TechNest mark: a "T" sitting in a nest. */
    public static function logo(string $class = 'logo'): string
    {
        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
            . '<rect width="32" height="32" rx="9" fill="currentColor"/>'
            . '<path d="M9 10.5h14M16 10.5v11" stroke="#fff" stroke-width="3.2" stroke-linecap="round" fill="none"/>'
            . '<path d="M8.5 19.5c1.8 3.2 4.4 4.8 7.5 4.8s5.7-1.6 7.5-4.8" stroke="#fff" stroke-width="2.4" stroke-linecap="round" fill="none" opacity=".75"/>'
            . '</svg>';
    }
}

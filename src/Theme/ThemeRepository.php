<?php

declare(strict_types=1);

namespace TechNest\Theme;

use TechNest\Database\Database;
use TechNest\Database\Row;

/**
 * Store-wide themes. A theme is a set of CSS custom properties selected by a
 * data-theme attribute on <html>, so switching is one UPDATE and no
 * stylesheet swap — and the admin page can preview each theme inline.
 */
final class ThemeRepository
{
    public const DEFAULT = 'regular';

    public function __construct(private readonly Database $db) {}

    /** @return list<array{slug: string, name: string, active: bool}> */
    public function all(): array
    {
        return array_map(static fn(array $r): array => [
            'slug' => Row::string($r, 'slug'),
            'name' => Row::string($r, 'name'),
            'active' => Row::bool($r, 'is_active'),
        ], $this->db->fetchAll('SELECT slug, name, is_active FROM themes ORDER BY id'));
    }

    public function active(): string
    {
        $slug = $this->db->fetchValue('SELECT slug FROM themes WHERE is_active = 1 ORDER BY id LIMIT 1');

        return is_string($slug) ? $slug : self::DEFAULT;
    }

    /** Make exactly one theme active. Returns false for an unknown slug. */
    public function activate(string $slug): bool
    {
        return $this->db->transaction(function (Database $db) use ($slug): bool {
            if ($db->fetchInt('SELECT COUNT(*) FROM themes WHERE slug = ?', [$slug]) === 0) {
                return false;
            }
            $db->execute('UPDATE themes SET is_active = CASE WHEN slug = ? THEN 1 ELSE 0 END', [$slug]);

            return true;
        });
    }
}

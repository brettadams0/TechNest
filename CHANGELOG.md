# Changelog

## 2.0.0 — 2026-10

A rebuild of the original coursework into a production-shaped application. Highlights:

### Architecture
- Front controller, router and per-request service container replace one PHP file per page.
- PSR-4 autoloading via Composer; no runtime dependencies.
- SQLite as a zero-configuration default alongside MySQL 8, with a migration runner.

### Fixed
- Admin pages included files by relative paths that did not resolve, so most of the admin panel
  failed.
- Checkout wrote one order line per selected *option* rather than per cart line, and none for
  products without options.
- Posted option ids were trusted: omitting a priced option bought the product at the base price,
  and another product's option id was accepted.
- Login ignored disabled accounts and kept the pre-login session id.
- Order status accepted any string.
- `monitor.php` exposed the PHP version publicly; the About and FAQ pages embedded media files that
  did not exist.

### Added
- CSRF protection enforced for every non-GET request, route-level guards, a strict
  Content-Security-Policy and security headers, safe post-login redirects.
- Login throttling per account and IP, shared by the password-change form; sessions signed out
  when the account's password changes.
- Transactional checkout with atomic stock decrement, price snapshots, Canadian address validation,
  shipping and HST.
- Order lifecycle state machine with audit history, restocking on cancel, and customer cancellation.
- Catalog search, category filter, sorting and pagination; product options with live pricing.
- Admin dashboard with period-over-period KPIs and server-rendered SVG charts; product options
  editor; product archiving; admin grant/revoke.
- Three themes as CSS design tokens with live previews; responsive layout throughout.
- Generated product illustrations, replacing hotlinked stock photos.
- PHPUnit suites (unit, integration, HTTP) on SQLite and MySQL; PHPStan at level max;
  php-cs-fixer; GitHub Actions; Docker image and Compose file.

### Removed
- Hard deletion of products (now archived).
- The installation, admin and end-user guides, replaced by accurate, shorter ones in `docs/`.

## 1.x — 2025

University of Windsor coursework: a PHP/MySQL store with a catalog, cart, checkout and admin pages.

# TechNest

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8%2B-777bb4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8%2B-4479a1.svg)](https://www.mysql.com/)

A full-stack electronics store in PHP and MySQL — catalog, accounts, cart, checkout, order
management and a separate admin panel — written without a framework, so the routing, the session
handling and the SQL are all on the page rather than hidden behind one.

## What's in it

**Storefront** — a 20-product catalog with per-product options, product pages, a session cart, and
checkout that writes real `orders` / `order_items` rows. Registration and login store passwords
with `password_hash` and verify with `password_verify`.

**Admin panel** (`public_html/admin/`) — manage products, users, orders and themes. Access is gated
on a row existing in the `admin` table rather than a flag on the user record, so admin rights are
granted and revoked without touching the account itself.

**Three switchable themes** — Regular, Minimal and Holiday, stored in a `themes` table with one
marked active and applied site-wide. Plus a `monitor.php` status page and a help wiki.

## Setup

Needs PHP 8+, MySQL 8+, and a web server (or PHP's built-in one).

```sh
mysql -u root -p -e "CREATE DATABASE technest CHARACTER SET utf8mb4;
  CREATE USER 'technest_user'@'localhost' IDENTIFIED BY 'choose-a-password';
  GRANT ALL ON technest.* TO 'technest_user'@'localhost';"

mysql -u technest_user -p technest < db_schema.sql   # tables
mysql -u technest_user -p technest < db_seed.sql     # 20 products, options, themes
```

Credentials come from the environment. There are **no defaults in the repo** — deliberately, since
this is a public repository:

```sh
cd public_html
DB_NAME=technest DB_USER=technest_user DB_PASS=choose-a-password DB_HOST=localhost \
  php -S 127.0.0.1:8000 -t .
```

Open <http://127.0.0.1:8000>. To give yourself admin, register an account and insert its `user_id`
into the `admin` table.

## Documentation

The detailed guides are the bulk of the written work here:

| | |
|---|---|
| [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) | Full deployment, including LAMP and shared hosting |
| [ADMIN_USER_GUIDE.md](ADMIN_USER_GUIDE.md) | Managing products, users, orders and themes |
| [END_USER_GUIDE.md](END_USER_GUIDE.md) | Browsing, accounts, cart and checkout |

## Layout

```
includes/            config, db connection, auth, shared header/footer
includes/admin/      admin-only page logic
public_html/         document root — storefront pages
public_html/admin/   admin panel
public_html/assets/  themes and static files
db_schema.sql        tables
db_seed.sql          20 products with options, plus the three themes
```

## Scope and limitations

Coursework, built for a university LAMP host, and worth being clear about:

- **No payment processing.** Checkout records an order; it does not charge anything.
- **Product images are hotlinked to Unsplash**, so the catalog needs internet access to render them
  and shows broken tiles offline.
- **No test suite and no CI.** The database work was verified by hand against the seed data.
- Passwords are hashed, but there is no email verification, no password reset, and no rate limiting
  on login.

## License

MIT — see [LICENSE](LICENSE).

-- TechNest schema, SQLite flavour. Kept structurally identical to
-- ../mysql/001_initial_schema.sql; only the column types differ.
--
-- Conventions:
--   * money is INTEGER cents, never a float or DECIMAL
--   * timestamps are UTC 'YYYY-MM-DD HH:MM:SS', written by the application
--   * order lines snapshot the product name, options and price at purchase,
--     so editing or archiving a product never rewrites order history

CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT    NOT NULL COLLATE NOCASE UNIQUE,
    email         TEXT    NOT NULL COLLATE NOCASE UNIQUE,
    password_hash TEXT    NOT NULL,
    is_active     INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_at    TEXT    NOT NULL
);

-- Admin rights are a row here rather than a flag on users, so granting and
-- revoking them never touches the account itself and leaves an audit trail.
CREATE TABLE admins (
    user_id    INTEGER PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE,
    granted_at TEXT    NOT NULL,
    granted_by INTEGER NULL REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    throttle_key TEXT    NOT NULL,
    attempted_at TEXT    NOT NULL
);
CREATE INDEX login_attempts_key_time ON login_attempts (throttle_key, attempted_at);

CREATE TABLE themes (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    slug      TEXT    NOT NULL UNIQUE,
    name      TEXT    NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 0 CHECK (is_active IN (0, 1))
);

CREATE TABLE categories (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    slug       TEXT    NOT NULL UNIQUE,
    name       TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES categories (id),
    slug        TEXT    NOT NULL UNIQUE,
    name        TEXT    NOT NULL,
    description TEXT    NOT NULL DEFAULT '',
    image       TEXT    NULL,
    price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
    stock       INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    is_featured INTEGER NOT NULL DEFAULT 0 CHECK (is_featured IN (0, 1)),
    is_active   INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);
CREATE INDEX products_category ON products (category_id, is_active);

CREATE TABLE product_options (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id  INTEGER NOT NULL REFERENCES products (id) ON DELETE CASCADE,
    group_name  TEXT    NOT NULL,
    value       TEXT    NOT NULL,
    extra_cents INTEGER NOT NULL DEFAULT 0 CHECK (extra_cents >= 0),
    sort_order  INTEGER NOT NULL DEFAULT 0,
    UNIQUE (product_id, group_name, value)
);

CREATE TABLE orders (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL REFERENCES users (id),
    status           TEXT    NOT NULL CHECK (status IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled')),
    subtotal_cents   INTEGER NOT NULL CHECK (subtotal_cents >= 0),
    shipping_cents   INTEGER NOT NULL CHECK (shipping_cents >= 0),
    tax_cents        INTEGER NOT NULL CHECK (tax_cents >= 0),
    total_cents      INTEGER NOT NULL CHECK (total_cents >= 0),
    ship_name        TEXT    NOT NULL,
    ship_street      TEXT    NOT NULL,
    ship_city        TEXT    NOT NULL,
    ship_province    TEXT    NOT NULL,
    ship_postal_code TEXT    NOT NULL,
    created_at       TEXT    NOT NULL,
    updated_at       TEXT    NOT NULL
);
CREATE INDEX orders_user_created ON orders (user_id, created_at);
CREATE INDEX orders_created ON orders (created_at);
CREATE INDEX orders_status ON orders (status);

CREATE TABLE order_items (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id         INTEGER NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id       INTEGER NOT NULL REFERENCES products (id),
    product_name     TEXT    NOT NULL,
    options_label    TEXT    NOT NULL DEFAULT '',
    unit_price_cents INTEGER NOT NULL CHECK (unit_price_cents >= 0),
    quantity         INTEGER NOT NULL CHECK (quantity > 0),
    line_total_cents INTEGER NOT NULL CHECK (line_total_cents >= 0)
);
CREATE INDEX order_items_order ON order_items (order_id);
CREATE INDEX order_items_product ON order_items (product_id);

CREATE TABLE order_status_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id    INTEGER NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    from_status TEXT    NULL,
    to_status   TEXT    NOT NULL,
    changed_by  INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
    changed_at  TEXT    NOT NULL
);
CREATE INDEX order_status_history_order ON order_status_history (order_id);

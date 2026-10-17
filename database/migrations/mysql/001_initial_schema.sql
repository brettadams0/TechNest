-- TechNest schema, MySQL 8 flavour. Kept structurally identical to
-- ../sqlite/001_initial_schema.sql; only the column types differ.
--
-- Conventions:
--   * money is INT cents, never a float or DECIMAL
--   * timestamps are UTC DATETIME, written by the application
--   * order lines snapshot the product name, options and price at purchase,
--     so editing or archiving a product never rewrites order history

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(30)  NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    -- Bumped on password change; sessions holding an older value end.
    session_version INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL,
    UNIQUE KEY users_username (username),
    UNIQUE KEY users_email (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- Admin rights are a row here rather than a flag on users, so granting and
-- revoking them never touches the account itself and leaves an audit trail.
CREATE TABLE admins (
    user_id    INT UNSIGNED NOT NULL PRIMARY KEY,
    granted_at DATETIME     NOT NULL,
    granted_by INT UNSIGNED NULL,
    CONSTRAINT admins_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT admins_granted_by FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    throttle_key CHAR(64)        NOT NULL,
    attempted_at DATETIME        NOT NULL,
    KEY login_attempts_key_time (throttle_key, attempted_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE themes (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug      VARCHAR(40)  NOT NULL,
    name      VARCHAR(60)  NOT NULL,
    is_active TINYINT(1)   NOT NULL DEFAULT 0,
    UNIQUE KEY themes_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE categories (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(60)  NOT NULL,
    name       VARCHAR(80)  NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    UNIQUE KEY categories_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT         NOT NULL,
    image       VARCHAR(120) NULL,
    price_cents INT UNSIGNED NOT NULL,
    stock       INT UNSIGNED NOT NULL DEFAULT 0,
    is_featured TINYINT(1)   NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL,
    updated_at  DATETIME     NOT NULL,
    UNIQUE KEY products_slug (slug),
    KEY products_category (category_id, is_active),
    CONSTRAINT products_category_fk FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE product_options (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    group_name  VARCHAR(40)  NOT NULL,
    value       VARCHAR(60)  NOT NULL,
    extra_cents INT UNSIGNED NOT NULL DEFAULT 0,
    sort_order  INT          NOT NULL DEFAULT 0,
    UNIQUE KEY product_options_unique (product_id, group_name, value),
    CONSTRAINT product_options_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    status           VARCHAR(12)  NOT NULL,
    subtotal_cents   INT UNSIGNED NOT NULL,
    shipping_cents   INT UNSIGNED NOT NULL,
    tax_cents        INT UNSIGNED NOT NULL,
    total_cents      INT UNSIGNED NOT NULL,
    ship_name        VARCHAR(80)  NOT NULL,
    ship_street      VARCHAR(120) NOT NULL,
    ship_city        VARCHAR(60)  NOT NULL,
    ship_province    CHAR(2)      NOT NULL,
    ship_postal_code VARCHAR(7)   NOT NULL,
    created_at       DATETIME     NOT NULL,
    updated_at       DATETIME     NOT NULL,
    KEY orders_user_created (user_id, created_at),
    KEY orders_created (created_at),
    KEY orders_status (status),
    CONSTRAINT orders_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT orders_status_valid CHECK (status IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled'))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE order_items (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NOT NULL,
    product_name     VARCHAR(120) NOT NULL,
    options_label    TEXT         NOT NULL,
    unit_price_cents INT UNSIGNED NOT NULL,
    quantity         INT UNSIGNED NOT NULL,
    line_total_cents INT UNSIGNED NOT NULL,
    KEY order_items_order (order_id),
    KEY order_items_product (product_id),
    CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT order_items_quantity_positive CHECK (quantity > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE order_status_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    from_status VARCHAR(12)  NULL,
    to_status   VARCHAR(12)  NOT NULL,
    changed_by  INT UNSIGNED NULL,
    changed_at  DATETIME     NOT NULL,
    KEY order_status_history_order (order_id),
    CONSTRAINT order_status_history_order_fk FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT order_status_history_user_fk FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

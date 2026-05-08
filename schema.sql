-- ============================================================
--  schema.sql — SuperStock Database Schema
--
--  HOW TO USE:
--  1. Open phpMyAdmin (http://localhost/phpmyadmin)
--  2. Create a new database called: superstock_mysqli
--  3. Select that database, click the "SQL" tab
--  4. Paste this entire file and click "Go"
--
--  This script is safe to re-run because it uses
--  "CREATE TABLE IF NOT EXISTS" and "INSERT IGNORE".
-- ============================================================

-- Use the correct database
-- (Uncomment the line below if you want the script to select it automatically)
-- USE superstock_mysqli;

-- ============================================================
--  TABLE: categories
--  Stores product categories (e.g. Dairy, Bakery).
--  Categories are managed in the database, not hardcoded in HTML.
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name VARCHAR(80)     NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_category_name (name)   -- no duplicate category names
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: users
--  Stores login accounts. Passwords are stored as bcrypt hashes,
--  never as plain text.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED                NOT NULL AUTO_INCREMENT,
    username      VARCHAR(80)                 NOT NULL,
    password_hash VARCHAR(255)                NOT NULL,
    role          ENUM('Admin', 'Manager')    NOT NULL,
    created_at    TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username)   -- no two users can share a username
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: products
--  Stores inventory items. Each product belongs to one category.
--  The foreign key prevents deleting a category that still has products.
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(255)    NOT NULL,
    category_id     INT UNSIGNED    NOT NULL,
    quantity        INT             NOT NULL DEFAULT 0,
    price           DECIMAL(10, 2)  NOT NULL,
    production_date DATE            NOT NULL,
    expiry_date     DATE            NOT NULL,
    supplier        VARCHAR(255)    NULL,
    notes           TEXT            NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Link each product to a category row
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id)
        REFERENCES categories (id)
        ON DELETE RESTRICT   -- block deletion of a category that has products
        ON UPDATE CASCADE,

    -- Business rule: expiry must be after production
    -- (MySQL 8.0+ supports CHECK constraints)
    CONSTRAINT chk_expiry_after_production
        CHECK (expiry_date > production_date)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: settings
--  Stores a single row of application-wide settings.
--  id is always 1 — there is only one settings record.
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id                          TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    store_name                  VARCHAR(255)        NOT NULL DEFAULT 'SuperStock Market',
    manager_name                VARCHAR(255)        NOT NULL DEFAULT 'John Smith',
    expiry_threshold_days       INT                 NOT NULL DEFAULT 7,
    low_stock_threshold         INT                 NOT NULL DEFAULT 10,
    notification_email          VARCHAR(255)        NULL,
    notification_telegram_token VARCHAR(255)        NULL,
    notification_telegram_chat_id VARCHAR(255)      NULL,

    PRIMARY KEY (id),
    -- Ensure only one row can ever exist
    CONSTRAINT chk_single_settings_row CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  SEED DATA: Default categories
--  INSERT IGNORE skips the row if the name already exists.
-- ============================================================
INSERT IGNORE INTO categories (name) VALUES
    ('Dairy'),
    ('Bakery'),
    ('Meat'),
    ('Beverages'),
    ('Frozen'),
    ('Snacks'),
    ('Produce'),
    ('Canned'),
    ('Other');


-- ============================================================
--  SEED DATA: Default Admin user
--
--  Username : admin
--  Password : admin123
--
--  The password_hash below was generated with:
--      password_hash('admin123', PASSWORD_BCRYPT)
--
--  IMPORTANT: Change this password after your first login!
-- ============================================================
INSERT INTO users (username, password_hash, role) VALUES (
    'admin',
    '$2y$12$UAEB4PzrBQh46mkUxw3ebeqwdMmiXnoBsnJOdYZdveEK.axNK3kqC',
    'Admin'
) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);

-- Also seed a default Manager account
-- Username : manager
-- Password : manager123
INSERT INTO users (username, password_hash, role) VALUES (
    'manager',
    '$2y$12$94XEOKp8X8EdyNDT8zWiMeMfRwVaoikABHy/3ICulDey6c5AhbWti',
    'Manager'
) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);


-- ============================================================
--  SEED DATA: Default settings row
--  INSERT IGNORE skips if id=1 already exists.
-- ============================================================
INSERT IGNORE INTO settings (id, store_name, manager_name, expiry_threshold_days, low_stock_threshold)
VALUES (1, 'SuperStock Market', 'John Smith', 7, 10);

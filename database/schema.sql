CREATE DATABASE IF NOT EXISTS f2b_shop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE f2b_shop;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    original_price DECIMAL(10, 2) NULL,
    rating DECIMAL(2, 1) NOT NULL DEFAULT 0.0,
    review_count INT UNSIGNED NOT NULL DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    image_2 VARCHAR(255) NULL,
    image_3 VARCHAR(255) NULL,
    image_4 VARCHAR(255) NULL,
    colour VARCHAR(50) NOT NULL DEFAULT 'Black',
    is_new TINYINT(1) NOT NULL DEFAULT 0,
    is_top_selling TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category_id (category_id),
    KEY idx_products_new (is_new),
    KEY idx_products_top_selling (is_top_selling),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_products_price_non_negative CHECK (price >= 0),
    CONSTRAINT chk_products_original_price_non_negative CHECK (original_price IS NULL OR original_price >= 0),
    CONSTRAINT chk_products_rating_range CHECK (rating >= 0 AND rating <= 5)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    delivery_address VARCHAR(500) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    special_instructions TEXT NULL,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status VARCHAR(32) NOT NULL DEFAULT 'ORDER_PLACED',
    access_token_hash CHAR(64) NULL,
    email_sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_status (status),
    KEY idx_orders_created_at (created_at),
    UNIQUE KEY uq_orders_access_token_hash (access_token_hash),
    CONSTRAINT chk_orders_subtotal_non_negative CHECK (subtotal >= 0),
    CONSTRAINT chk_orders_discount_non_negative CHECK (discount >= 0),
    CONSTRAINT chk_orders_delivery_fee_non_negative CHECK (delivery_fee >= 0),
    CONSTRAINT chk_orders_total_non_negative CHECK (total >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    size VARCHAR(10) NOT NULL,
    colour VARCHAR(50) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order_id (order_id),
    KEY idx_order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_order_items_unit_price_non_negative CHECK (unit_price >= 0),
    CONSTRAINT chk_order_items_quantity_positive CHECK (quantity > 0),
    CONSTRAINT chk_order_items_line_total_non_negative CHECK (line_total >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mockups (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    order_item_id INT UNSIGNED NOT NULL,
    preview_image VARCHAR(255) NOT NULL,
    print_position VARCHAR(100) NOT NULL DEFAULT 'Front',
    print_colour VARCHAR(100) NOT NULL DEFAULT 'Black',
    custom_text VARCHAR(500) NULL,
    status ENUM('PENDING', 'READY_FOR_REVIEW', 'CHANGES_REQUESTED', 'APPROVED') NOT NULL DEFAULT 'PENDING',
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mockups_order_item_id (order_item_id),
    KEY idx_mockups_order_id (order_id),
    KEY idx_mockups_status (status),
    CONSTRAINT fk_mockups_order
        FOREIGN KEY (order_id)
        REFERENCES orders (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_mockups_order_item
        FOREIGN KEY (order_item_id)
        REFERENCES order_items (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_mockups_revision_positive CHECK (revision > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mockup_reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mockup_id INT UNSIGNED NOT NULL,
    decision ENUM('approve', 'request_changes') NOT NULL,
    change_request TEXT NULL,
    revision INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mockup_reviews_mockup_id (mockup_id),
    KEY idx_mockup_reviews_revision (mockup_id, revision),
    CONSTRAINT fk_mockup_reviews_mockup
        FOREIGN KEY (mockup_id)
        REFERENCES mockups (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_mockup_reviews_revision_positive CHECK (revision > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

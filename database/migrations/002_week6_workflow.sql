USE f2b_shop;

-- This migration is additive and safe to run against the Week 5 database.
-- Existing orders remain intact and receive NULL workflow fields until a new
-- checkout order is created with a guest access token.

SET @database_name = DATABASE();

SELECT COUNT(*) INTO @has_access_token_hash
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @database_name
  AND TABLE_NAME = 'orders'
  AND COLUMN_NAME = 'access_token_hash';

SET @sql = IF(
    @has_access_token_hash = 0,
    'ALTER TABLE orders ADD COLUMN access_token_hash CHAR(64) NULL AFTER status',
    'SELECT 1'
);
PREPARE week6_statement FROM @sql;
EXECUTE week6_statement;
DEALLOCATE PREPARE week6_statement;

SELECT COUNT(*) INTO @has_email_sent_at
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @database_name
  AND TABLE_NAME = 'orders'
  AND COLUMN_NAME = 'email_sent_at';

SET @sql = IF(
    @has_email_sent_at = 0,
    'ALTER TABLE orders ADD COLUMN email_sent_at DATETIME NULL AFTER access_token_hash',
    'SELECT 1'
);
PREPARE week6_statement FROM @sql;
EXECUTE week6_statement;
DEALLOCATE PREPARE week6_statement;

SELECT COUNT(*) INTO @has_access_token_index
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @database_name
  AND TABLE_NAME = 'orders'
  AND INDEX_NAME = 'uq_orders_access_token_hash';

SET @sql = IF(
    @has_access_token_index = 0,
    'ALTER TABLE orders ADD UNIQUE KEY uq_orders_access_token_hash (access_token_hash)',
    'SELECT 1'
);
PREPARE week6_statement FROM @sql;
EXECUTE week6_statement;
DEALLOCATE PREPARE week6_statement;

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

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  original_price DECIMAL(10,2) NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count INT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(255) NOT NULL,
  image_2 VARCHAR(255) NULL,
  image_3 VARCHAR(255) NULL,
  image_4 VARCHAR(255) NULL,
  colour VARCHAR(50) NOT NULL,
  is_new TINYINT(1) NOT NULL DEFAULT 0,
  is_top_selling TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_category_price (category_id, price, id),
  KEY idx_products_colour_price (colour, price, id),
  KEY idx_products_newest (created_at, id),
  KEY idx_products_featured (is_top_selling, is_new, id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_products_price CHECK (price >= 0),
  CONSTRAINT chk_products_original_price CHECK (original_price IS NULL OR original_price >= 0),
  CONSTRAINT chk_products_rating CHECK (rating >= 0 AND rating <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(254) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  delivery_address VARCHAR(500) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  special_instructions VARCHAR(1000) NOT NULL DEFAULT '',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(32) NOT NULL DEFAULT 'ORDER_PLACED',
  access_token_hash CHAR(64) NOT NULL,
  idempotency_key VARCHAR(80) NOT NULL,
  email_status ENUM('disabled','queued','failed','sent') NOT NULL DEFAULT 'disabled',
  email_sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_access_token_hash (access_token_hash),
  UNIQUE KEY uq_orders_idempotency_key (idempotency_key),
  KEY idx_orders_status_created (status, created_at, id),
  CONSTRAINT chk_orders_subtotal CHECK (subtotal >= 0),
  CONSTRAINT chk_orders_discount CHECK (discount >= 0),
  CONSTRAINT chk_orders_delivery_fee CHECK (delivery_fee >= 0),
  CONSTRAINT chk_orders_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_slug VARCHAR(180) NOT NULL,
  product_name VARCHAR(160) NOT NULL,
  image VARCHAR(255) NOT NULL,
  size VARCHAR(10) NOT NULL,
  colour VARCHAR(50) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity TINYINT UNSIGNED NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id, id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_order_items_quantity CHECK (quantity BETWEEN 1 AND 99),
  CONSTRAINT chk_order_items_size CHECK (size IN ('S','M','L','XL','ONE SIZE'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mockups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  order_item_id INT UNSIGNED NOT NULL,
  preview_image VARCHAR(255) NOT NULL,
  print_position VARCHAR(100) NOT NULL,
  print_colour VARCHAR(100) NOT NULL,
  custom_text VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('PENDING','READY_FOR_REVIEW','CHANGES_REQUESTED','APPROVED') NOT NULL DEFAULT 'PENDING',
  revision INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mockups_order_item (order_item_id),
  KEY idx_mockups_order_status (order_id, status),
  CONSTRAINT fk_mockups_order FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_mockups_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_mockups_revision CHECK (revision >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mockup_reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mockup_id INT UNSIGNED NOT NULL,
  decision ENUM('approve','request_changes') NOT NULL,
  change_request VARCHAR(500) NOT NULL DEFAULT '',
  revision INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mockup_reviews_history (mockup_id, id),
  KEY idx_mockup_reviews_revision (mockup_id, revision),
  CONSTRAINT fk_mockup_reviews_mockup FOREIGN KEY (mockup_id) REFERENCES mockups (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_mockup_reviews_revision CHECK (revision >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  sid VARCHAR(128) NOT NULL,
  sess MEDIUMTEXT NOT NULL,
  expires DATETIME(3) NOT NULL,
  PRIMARY KEY (sid),
  KEY idx_sessions_expires (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

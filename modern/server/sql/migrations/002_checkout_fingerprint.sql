ALTER TABLE orders ADD COLUMN checkout_fingerprint CHAR(64) NULL AFTER idempotency_key;

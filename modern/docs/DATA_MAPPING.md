# Modern Version data mapping

The Modern Version stores all application data in its own XAMPP MySQL database. See [MYSQL_DATA_MAPPING.md](MYSQL_DATA_MAPPING.md) for the column-level mapping and [the migration audit](MYSQL_MIGRATION_AUDIT.md) for the source inventory. No PHP customer/order rows are copied. The only imported seed data is the six source categories and 64 source products.

| Feature | MySQL storage | Integrity and API behavior |
| --- | --- | --- |
| Catalogue | `categories`, `products` | Source numeric IDs and image order stay stable; products reference categories with a foreign key; money uses `DECIMAL(10,2)` and integer-cent DTOs. |
| Cart and session | `sessions`; session cart contains product ID, size, colour and quantity only | Product state and price are re-read from SQL on each request. Session rows expire and are pruned. |
| Checkout | `orders`, `order_items` | One InnoDB transaction writes header and immutable line snapshots. Auto-increment order number and item IDs are stable public IDs. Cart clears after commit. |
| Guest access and retries | `orders.access_token_hash`, `idempotency_key`, `checkout_fingerprint` | SQL stores SHA-256 only. A unique idempotency key and request fingerprint return the matching prior order or reject conflicting reuse. Raw access code stays in session and is shown once. |
| Staff/mock-ups | `mockups`, `mockup_reviews` | Unique order-item mock-up, composite ownership FK, guarded row-lock transactions, stable numeric IDs, and append-only review records. |
| Local receipt | `orders.email_status`, `email_sent_at`, `email_claimed_at` | Atomic lease and status updates follow order commit. Default disabled; loopback SMTP and exact local-recipient allowlist only. |

`f2b_shop_modern` is the local development database. `f2b_shop_modern_test` is the isolated API integration database. Both are loopback-only and protected by explicit name guards. The root PHP database `f2b_shop` is never a Modern target.

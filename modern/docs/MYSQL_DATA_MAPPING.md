# Modern MySQL data mapping

The Modern database is independent of the root PHP database. The only source data imported is the existing product/category seed: 6 categories and 64 catalogue products, with their image order preserved. Do not copy PHP customer/order rows. Development targets `f2b_shop_modern`; automated integration tests target only `f2b_shop_modern_test`.

| Current document/value | Relational representation | Keys, constraints, and API mapping |
| --- | --- | --- |
| `Category.legacyId`, name, slug | `categories(id, name, slug, timestamps)` | Preserve legacy category number as `id`; primary key; unique slug. Product joins supply category name/slug to existing product DTO. |
| `Product.legacyId` | `products.id` | Preserve source numeric product ID as primary key/API `id`, not an opaque DB identifier. |
| `Product.categoryRef` ObjectId, `categorySlug` | `products.category_id` FK to `categories.id` | Remove Mongo reference and redundant slug; join categories for DTO/filter. FK uses `ON UPDATE CASCADE`, `ON DELETE RESTRICT`. |
| Product name, slug, description, price cents, original price cents, rating, review count | `products.name`, `slug`, `description`, `price DECIMAL(10,2)`, nullable `original_price DECIMAL(10,2)`, `rating DECIMAL(2,1)`, `review_count` | Unique slug, bounds/checks where supported. Parse decimal strings to integer cents; do not calculate money with JS floating-point. |
| Product images array (1–4), colour, `isNew`, `isTopSelling`, `legacyCreatedAt` | `image`, nullable `image_2`–`image_4`, `colour`, boolean flags, `created_at` | Image columns retain source order and map back to the existing `images[]` and primary `image` DTO. Preserve date order/sort keys. |
| `Counter(_id='orders').sequence`, `Order.orderNumber` | `orders.id AUTO_INCREMENT` | A successful committed order's numeric primary key is its public order number. Gaps after rollback are acceptable; numbers remain unique and monotonic. No counter collection/table required. |
| Order customer fields and status | `orders.full_name`, `email`, `phone`, `delivery_address`, `postal_code`, `special_instructions`, `status`, `created_at` | Same source length constraints and API camel-case DTO. Allowed statuses: `ORDER_PLACED`, `DESIGN_REVIEW`, `CHANGES_REQUESTED`, `CONFIRMED`, `PROCESSING`, `COMPLETED`. Staff DTO alone receives contact/address fields. |
| Order cents totals | `orders.subtotal`, `discount`, `delivery_fee`, `total DECIMAL(10,2)` | Convert cents to exact decimal strings for parameter binding and read as decimal strings, then convert to cents. Preserve 10% PHP half-up discount and $5 nonempty delivery. |
| `Order.items[]` snapshot | One `order_items` row per snapshot | `id` auto-increment stable API ID; `order_id` FK cascade; `product_id` FK restrict plus immutable `product_name`, `size`, `colour`, `unit_price`, `quantity`, `line_total`. Product edits must not mutate saved snapshot columns. |
| `accessCodeHash` | `orders.access_token_hash CHAR(64)` | Unique SHA-256 hex; excluded from all DTOs/select lists except internal lookup. Raw 48-hex guest code exists only in the session and is consumed on first confirmation read. |
| `idempotencyKey` + normalized submitted checkout and cart | `orders.idempotency_key VARCHAR(80)` unique, `checkout_fingerprint CHAR(64)` | Unique constraint arbitrates concurrent duplicate checkout. Insert order and items on the same connection/transaction; a matching fingerprint returns the committed order while conflicting key reuse returns 409. |
| `emailStatus`, `emailSentAt` and in-flight claim | `orders.email_status`, `email_sent_at`, `email_claimed_at` | `disabled`, `queued`, `failed`, `sent`; public response may report `already_sent` when appropriate. Atomic conditional claims use a 10-minute lease. SMTP runs only after commit and never gates order creation; `sent` records SMTP acceptance, not inbox delivery. |
| Mock-up document | `mockups` | Auto-increment `id` is stable public mock-up ID; `order_id` and unique `order_item_id` FKs; local `preview_image`, print fields, custom text, status, revision, timestamps. |
| Embedded review history | `mockup_reviews` | Auto-increment ID, `mockup_id` FK, decision, optional request, revision, timestamp. Append only; enforce valid decision and revision in SQL/backend. |
| Express session | `sessions(sid, sess, expires)` | Persistent `express-session` data contains CSRF, staff flag, cart, last checkout code, order grants, and staff login timestamp. Primary key `sid`; expiry index and TTL cleanup. Use store-supported atomic writes and safe cookie/session rotation. |

## Expected DTO identity mapping

- Product IDs and category IDs stay numeric and match the seed.
- `orderNumber` is the numeric `orders.id`.
- Order item `id` is numeric `order_items.id` serialized as a decimal string to preserve the current client type.
- Mock-up `mockupId` and customer route `:id` are numeric `mockups.id` serialized as a decimal string.
- SQL aliases and explicit row mappers preserve current JSON property names, envelopes, status codes and omission of sensitive columns.

## SQL safety and database isolation

- Only `DB_HOST=127.0.0.1` or an explicit loopback alias accepted by the configuration is permitted for setup/tests.
- Normal runtime allowlists `f2b_shop_modern`; tests require exactly `f2b_shop_modern_test`.
- No Modern script may target `f2b_shop`, and no script uses `DROP DATABASE`. Initialization creates only the allowlisted Modern schema when the operator runs the explicit command.
- All query values use mysql2 placeholders. Dynamic sort keys, identifiers, and database names come only from fixed allowlists.
- Development seed uses `INSERT ... ON DUPLICATE KEY UPDATE` for catalogue rows only. It never clears orders, sessions, mock-ups, or reviews.

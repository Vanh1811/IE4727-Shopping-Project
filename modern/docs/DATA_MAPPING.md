# Legacy-to-modern data mapping

Source schema: `database/schema.sql`; catalogue source: `database/f2b_product_seed.sql`; workflow additions: `database/migrations/002_week6_workflow.sql`.

| MySQL table/column | Modern representation | Mapping requirement |
|---|---|---|
| `categories.id/name/slug` | `Category.legacyId/name/slug` | Preserve numeric ID and unique slug. |
| `products.id/category_id` | `Product.legacyId/category` | Preserve numeric product ID for existing `product.php?id=` behavior; reference Category and retain category slug. |
| `products.name/slug/description` | `Product.name/slug/description` | Exact text, unique slug. |
| `price`, `original_price` DECIMAL(10,2) | integer `priceCents`, nullable `originalPriceCents` | Parse decimal strings without binary-float conversion. |
| `rating`, `review_count` | `rating`, `reviewCount` | Preserve exact legacy values. |
| `image`, `image_2` … `image_4` | `images[]` | Preserve order and exact paths/URLs, omit null/empty entries; do not substitute images. |
| `colour`, `is_new`, `is_top_selling`, `created_at` | same values | Keep exact colour spelling, flags and timestamp; derive colour display hex from `includes/product_colour.php`. |
| `orders` + `order_items` | `Order` with embedded immutable `items[]` | Preserve public legacy order number when importing; copy product name, variant, unit price, qty and line total as snapshot. Never import existing customer PII by default. |
| order totals | integer cents fields | Store subtotal/discount/delivery/total; snapshots do not follow future catalogue changes. |
| `access_token_hash` | `accessCodeHash` | SHA-256 only; never persist original code. Existing rows with null hash are not made accessible by ID alone. |
| `status`, `email_sent_at`, `created_at` | status, mail state/time, timestamps | Preserve allowed state and honest local mail result. |
| `mockups` | `Mockup` | Reference order + stable embedded item UUID; unique order/item; safe local preview path; position/colour/text/status/revision/timestamps. |
| `mockup_reviews` | `MockupReview` | Append-only order/revision decisions and timestamp. |
| staff credentials/session | session only | No public user registration; configured password hash; Mongo-backed session storage. |

The catalogue seed uses `INSERT INTO categories ... VALUES` and a multiline `INSERT INTO products` statement. Stage 2 parses the whole file with `node-sql-parser` in MySQL mode, then accepts only literal `INSERT` values for these two tables. It verifies six categories, 64 products, unique category/product IDs and slugs, valid category references, and one to four images per product.

## Verified source seed snapshot

Parsed from `database/f2b_product_seed.sql` on Stage 2:

- Categories: **6** (`T-Shirts`, `Shirts`, `Hoodies & Sweatshirts`, `Jackets`, `Pants`, `Accessories`).
- Products: **64**, legacy IDs 1–64; **16** marked new and **16** marked top selling.
- Colours: **17** distinct stored values, preserved as written in SQL.
- Product gallery URLs: **256** retained, **256 unique**, **0 duplicates**.
- Price range: $24.90–$109.90; stored as integer cents.
- Source note: the SQL seed explicitly labels prices illustrative and product metadata/photos as third-party. Its own comments say image contents/colour and live URL availability were not visually verified. The modern app preserves the source URLs and will show an image fallback on load failure; it does not claim those photographs are verified.

The parser/unit checks are verified. Import into MongoDB and repeatability remain BLOCKED until a local Mongo replica set is running.

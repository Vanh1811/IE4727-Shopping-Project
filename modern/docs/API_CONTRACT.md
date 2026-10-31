# Modern API contract

All API routes are prefixed `/api/v1`; errors use JSON and never fall through to SPA HTML. Responses use `{ data, error }`; catalogue product IDs remain stable legacy numeric IDs and no storage-layer identifier is exposed.

## Stage 1 endpoints

| Method/path | Auth | Behavior |
|---|---|---|
| `GET /api/v1/health` | Public | `{ data: { status, database }, error: null }`; database status is sanitized (`connected`, `disconnected`, or `not_ready`). HTTP 200 only when XAMPP MySQL and all migrations are ready, 503 otherwise. |
| `GET /api/v1/session/csrf` | Guest session | Creates/returns session CSRF token; `Cache-Control: no-store`; HttpOnly session cookie. |
| `GET /api/v1/catalogue/meta` | Public | `{ categories, colours, priceFloor, priceCeiling }`; categories use legacy IDs/slugs and min/max dollars are floored/ceiled like `shop.php`. Empty catalogue fallback is 0–200. |
| `GET /api/v1/products` | Public | Search, repeated category/colour filters, nonnegative min/max price, sort and pagination. Returns `{ items, filters, pagination }`; default `page=1`, `limit=100` preserves all 64 baseline rows in one response. |
| `GET /api/v1/products/:id` | Public | Product by positive legacy numeric ID or slug; includes all source gallery URLs, variants metadata, cents, rating and colour swatch. Missing product is JSON 404. |
| `GET /api/v1/home` | Public | `stats`, up to four newest flagged `newArrivals`, and up to four newest `topSelling` products. |

## Catalogue query behavior

- `search` is preferred over the legacy alias `q`; name matching is case-insensitive and preserves SQL `LIKE` `%`/`_` wildcards. Search is limited to 2,000 characters.
- Repeat `category` and `colour` query keys to select multiple values. Unsupported values are ignored as in the PHP allowlists; colour comparison is case-insensitive.
- `min_price`/`max_price` are dollars. Invalid individual bounds are ignored and returned in `filters.notice`; min greater than max yields an empty result with the legacy notice.
- Sort keys are `featured`, `newest`, `price_asc`, `price_desc`; unknown keys fall back to `featured`. Tie-breaks match `shop.php` (`legacyId` ascending/descending as appropriate).
- Page must be 1–1,000,000; limit must be 1–100. Invalid pagination returns `400 INVALID_PAGINATION`.

## Response and security rules

- Success JSON is `{ "data": ..., "error": null }`.
- Error JSON is `{ "data": null, "error": { "code": "...", "message": "..." } }`.
- JSON parser is size-limited; errors are sanitized; stack traces and secrets are not returned.
- Browser origin is restricted to configured `CLIENT_ORIGIN`; Vite proxies `/api` in development.
- State-changing APIs require same-origin session and CSRF token. Cookie is HttpOnly, SameSite=Lax and Secure in production HTTPS.
- API routes are mounted before any client fallback. Stage 1 has no catch-all SPA route in Express; Vite handles its own dev fallback.

Cart, order, mock-up and staff endpoints will be added in their owning stages.

## Stage 3 storefront behavior

- The React home reads stats and featured product groups from `GET /home`; it does not embed a second catalogue copy.
- The shop reads `GET /catalogue/meta` and `GET /products`; selections are kept in the URL with repeated `category` and `colour` keys and legacy `search`/`q`, price, and sort parameter names.
- Product cards and detail pages use `GET /products/:id` with the source legacy numeric ID. Detail galleries keep the original SQL image URL order. A failed source image is labeled as unavailable instead of substituted with a different item.
- Cart mutation was not part of the Stage 3 checkpoint; Stage 4 adds its CSRF-protected endpoints below. No payment action is present.

## Stage 4 cart and checkout behavior

| Method/path | Auth | Behavior |
|---|---|---|
| `GET /api/v1/cart` | Guest session | Server session cart with current product snapshots and totals recomputed from current MySQL catalogue prices. Empty cart is valid. |
| `POST /api/v1/cart/items` | Session + CSRF | Validates current product, exact source colour, apparel/accessory size, quantity 1–99. Same variant combines up to 99. |
| `PATCH /api/v1/cart/items/:key` | Session + CSRF | Changes one existing line quantity after revalidating the catalogue option. |
| `DELETE /api/v1/cart/items/:key`, `DELETE /api/v1/cart` | Session + CSRF | Removes one variant or clears the bag. |
| `GET /api/v1/checkout` | Guest session | Returns current bag and totals; empty bags return `409 EMPTY_CART`. |
| `POST /api/v1/checkout` | Session + CSRF + `Idempotency-Key` | Uses the PHP field names/messages/limits, fetches current MySQL prices, creates the order and immutable item snapshots in one InnoDB transaction, then clears the cart. Initial state is `ORDER_PLACED`; this does not charge or confirm payment. Repeating the same key and payload returns the original order; reusing it with a different payload returns `409 IDEMPOTENCY_KEY_REUSED`. The optional local receipt runs after commit and never gates order success. |
| `GET /api/v1/orders/:orderNumber/confirmation` | Session-scoped order access | Returns saved totals/snapshots; initial raw 48-hex guest access code is consumed from the session on first read and is never stored in a database. Response is `no-store`. |

Cart lines contain only product ID, size, colour and quantity in the session. Subtotal, 10% discount, $5 delivery for nonempty carts and total use integer cents; discount rounds half up to match PHP `round()` for nonnegative values.

## Stage 7 local receipt behavior

- `LOCAL_MAIL_ENABLED` must equal `1`; `LOCAL_MAIL_TO` must be a valid local-domain address (`.test`, `.localhost`, `.local`, or `localhost`) and match `LOCAL_MAIL_ALLOWED_TO` case-insensitively.
- SMTP host must be literal `127.0.0.1` or `::1`; the transport does not use TLS, authentication, public DNS or relay. Default feature state is disabled.
- The message is plain text composed from the committed MySQL order/item snapshots. It uses the configured local inbox, never the checkout email. SMTP acceptance stamps `emailStatus: sent` and `emailSentAt`; a rejected handoff records `failed`; a concurrent send claim records `queued`. Stale claims are eligible for retry after a 10-minute lease. Refresh/repeat processing reports `already_sent` only after a prior accepted handoff.
- Mail is handed off after the order transaction commits. An SMTP failure does not undo the order. A transport acceptance followed by failure to persist the sent timestamp cannot provide exactly-once delivery; the implementation does not claim that guarantee.

## Stage 5 guest order access

| Method/path | Auth | Behavior |
|---|---|---|
| `POST /api/v1/orders/access` | CSRF + lookup rate limit | Accepts order number and access code in the JSON body, never the URL. Checks the SHA-256 code hash with constant-time comparison, regenerates the session ID, then grants access to that order. All misses return the same generic message. |
| `GET /api/v1/orders` | Session-scoped guest authorization | Lists only orders granted to the current session; an unauthenticated session receives an empty list. |
| `GET /api/v1/orders/:orderNumber` | Session-scoped guest authorization | Returns only an order number present in the current session. Knowing an order number is insufficient. |

Access codes are 24 cryptographically random bytes (48 lowercase hex characters); MySQL stores only SHA-256. Lookup accepts at most 128 characters and is limited to 10 failed attempts per 15 minutes per client IP. Successful recovery rotates the session ID while preserving the cart, existing order grants, and checkout confirmation state.

## Stage 6 staff and design workflow

| Method/path | Auth | Behavior |
|---|---|---|
| `GET /api/v1/staff/session` | Session | Reports only whether staff access is configured and whether this session is authenticated; never returns the password hash. |
| `POST /api/v1/staff/login`, `POST /api/v1/staff/logout` | CSRF; login rate limited | Verifies the configured PHP-compatible bcrypt hash, rotates the session, and preserves the guest cart/order grants. |
| `GET /api/v1/staff/orders` | Staff session | Paginated staff order queue with customer/contact details and per-item design state. |
| `GET /api/v1/staff/mockup-assets` | Staff session | Lists existing local files under `modern/client/public/images/products`. |
| `POST /api/v1/staff/orders/:orderNumber/items/:itemId/mockup` | Staff + CSRF | Publishes one preview per order item; only local allowlisted files; revision 1 initially and increments only after a customer change request. Stable SQL item/mock-up IDs are exposed as decimal strings. |
| `POST /api/v1/staff/orders/:orderNumber/status` | Staff + CSRF | Only `CONFIRMED → PROCESSING → COMPLETED`, with an atomic compare-and-update. |
| `GET /api/v1/mockups/:id` | Session-scoped guest access | Returns a mock-up only when its order is authorized in the current session. |
| `POST /api/v1/mockups/:id/review` | Order owner session + CSRF | Requires exact pending revision; appends review history and transactionally updates mock-up/order state. All items must be approved for `CONFIRMED`. |

Staff auth uses `STAFF_PASSWORD_HASH` from the server environment. State-changing staff/customer workflow writes lock the relevant InnoDB rows and verify expected state/revision before commit. Local preview URLs are stored as `/images/products/...`; paths are canonicalized against the modern local asset directory and symlinks/traversal are rejected.

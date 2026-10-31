# Modern API contract

All API routes are prefixed `/api/v1`; errors use JSON and never fall through to SPA HTML. Responses use `{ data, error }` and do not expose Mongo ObjectIds for product access; catalogue product IDs are stable legacy numeric IDs.

## Stage 1 endpoints

| Method/path | Auth | Behavior |
|---|---|---|
| `GET /api/v1/health` | Public | `{ data: { status, database }, error: null }`; database status is sanitized (`connected`, `disconnected`, or `not_configured`). HTTP 200 only when DB ready, 503 otherwise. |
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
- Cart mutation and payment remain out of scope for this stage; the product action asks for size and does not claim a cart write succeeded.

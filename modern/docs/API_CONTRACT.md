# Modern API contract

Stage 1 foundation contract. All public API routes are prefixed `/api/v1`; API errors use JSON and never fall through to SPA HTML. The full product, cart, order and staff endpoints are added in their owning stages.

## Stage 1 endpoints

| Method/path | Auth | Behavior |
|---|---|---|
| `GET /api/v1/health` | Public | `{ data: { status, database }, error: null }`; database status is sanitized (`connected`, `disconnected`, or `not_configured`). HTTP 200 only when DB ready, 503 otherwise. |
| `GET /api/v1/session/csrf` | Guest session | Creates/returns session CSRF token; `Cache-Control: no-store`; HttpOnly session cookie. |

## Response and security rules

- Success JSON is `{ "data": ..., "error": null }`.
- Error JSON is `{ "data": null, "error": { "code": "...", "message": "..." } }`.
- JSON parser is size-limited; errors are sanitized; stack traces and secrets are not returned.
- Browser origin is restricted to configured `CLIENT_ORIGIN`; Vite proxies `/api` in development.
- State-changing APIs require same-origin session and CSRF token. Cookie is HttpOnly, SameSite=Lax and Secure in production HTTPS.
- API routes are mounted before any client fallback. Stage 1 has no catch-all SPA route in Express; Vite handles its own dev fallback.

Later endpoint ownership is specified by the source implementation plan and will be finalized as each stage lands.

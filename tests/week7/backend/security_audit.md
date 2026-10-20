# Week 7 Backend Security Audit

## Scope and environment

This audit reviewed the Week 6 backend source from integrated `main` at `3110b2a`.
The review covered order privacy, guest access, staff authentication, CSRF,
injection/XSS handling, local image paths, session/error handling, abuse limits,
and HTTP method enforcement.

PHP CLI, MySQL/MariaDB and browser automation were unavailable in this
environment. The observations below are static source evidence only; no runtime
security case is claimed as PASS.

## Threat and test matrix

| Test ID | Endpoint / input | Expected authorization response | Observed source result | Severity | Status | Evidence / fix commit |
|---|---|---|---|---|---|---|
| B1-01 | `confirmation.php?order=<other order>` in a new session | Do not render order data or PII | Order is loaded only after `f2bOrderHasSessionAccess()`; unauthorised view falls back to access-required content | Critical | BLOCKED | `confirmation.php`, `includes/order_access.php`; runtime unavailable; audit-only commit |
| B1-02 | `mockup.php?mockup=<other mock-up>` in a new session | Do not render preview, order data or review history | Mock-up owner order is checked with `f2bOrderHasSessionAccess()` before detail/history queries | Critical | BLOCKED | `mockup.php`; runtime unavailable; audit-only commit |
| B1-03 | Invalid order number/access code submitted to `orders.php` | No session access elevation and generic failure | Prepared lookup, hashed token comparison and generic failure message are present; failed verification returns before session grant | Critical | BLOCKED | `orders.php`, `includes/order_access.php`; runtime unavailable; audit-only commit |
| B1-04 | Reuse an already displayed guest access code | Code is not exposed again after first reveal | Code is stored in a one-time session slot and removed by `f2bOrderConsumeInitialAccessCode()`; DB stores only SHA-256 hash | High | BLOCKED | `confirmation.php`, `includes/order_access.php`; runtime unavailable; audit-only commit |
| B1-05 | Valid guest access elevation | Session identifier is renewed before authorization is stored | `f2bOrderVerifyAccessCode()` calls `f2bOrderGrantSessionAccess(..., true)` and the grant regenerates the session ID | High | BLOCKED | `includes/order_access.php`; runtime unavailable; audit-only commit |
| B1-06 | Unauthenticated GET/POST to staff pages/actions | Redirect or reject; no staff state change | Staff pages call `f2bAdminRequireAuth()`; action endpoints require POST and CSRF; logout only clears privileges for valid POST+CSRF | Critical | BLOCKED | `admin-orders.php`, `admin-order-action.php`, `admin-logout.php`, `includes/admin_auth.php`; runtime unavailable; audit-only commit |
| B1-07 | Wrong staff password or missing server secret | No login and no default credential | Login uses `password_verify()` against `F2B_STAFF_PASSWORD_HASH`; no repository password/default credential found | Critical | BLOCKED | `admin-login.php`, `includes/admin_auth.php`; runtime unavailable; audit-only commit |
| B1-08 | Missing/forged CSRF on checkout, guest unlock, staff, review or cart actions | State change is rejected | Each state-changing endpoint validates a scoped `f2bCsrfToken()` value; cart forms remain Member A-owned and use the existing helper | Critical | BLOCKED | `checkout.php`, `orders.php`, `admin-login.php`, `admin-order-action.php`, `admin-logout.php`, `mockup-action.php`, `cart-action.php`; runtime unavailable; audit-only commit |
| B1-09 | SQL/XSS payloads in order, review, staff or search inputs | No SQL alteration or executable HTML | Backend queries use prepared statements; rendered user/database values use `htmlspecialchars()`; sort columns use an allowlist | High | BLOCKED | `includes/order.php`, `includes/review.php`, `includes/admin_order.php`, `shop.php`, `admin-orders.php`; runtime unavailable; audit-only commit |
| B1-10 | `../`, external URL, or executable path as staff preview image | Path is rejected and only an existing local product image is accepted | `f2bAdminLocalImagePath()` and customer fallback validation reject traversal/non-local paths and verify `realpath()` containment | High | BLOCKED | `includes/admin_order.php`, `mockup.php`; runtime unavailable; audit-only commit |
| B1-11 | Malformed IDs, arrays, missing DB connection or SQL failure | Safe failure without traces or bogus success | Positive-ID/scalar validation and generic user-facing errors are present; database details are logged generically | Major | BLOCKED | `includes/order_access.php`, `includes/order.php`, `includes/review.php`, `includes/db.php`; runtime unavailable; audit-only commit |
| B1-12 | Repeated failed staff/guest-code requests | Safe bounded behavior or documented limitation | No request throttling/rate limit was found. Password/token checks are bounded by scalar/length validation, but repeated-attempt mitigation remains unverified | Major | BLOCKED | `includes/admin_auth.php`, `includes/order_access.php`; runtime unavailable; no code fix claimed |
| B1-13 | Direct GET to order status, review, cart or staff state actions | No state mutation | Order status/mock-up/review/cart mutations are behind POST checks; GET pages perform reads only | Critical | BLOCKED | `admin-order-action.php`, `mockup-action.php`, `cart-action.php`, `checkout.php`; runtime unavailable; audit-only commit |

## Findings and ownership

- No confirmed source-level authorization defect was found that justified a
  backend code change in B1.
- The repeated-attempt mitigation is not implemented; it should be revisited
  if runtime abuse testing demonstrates a course-scope requirement. No claim
  of throttling is made.
- No Member A customer-form change is required from this static audit. The
  existing customer cart, checkout and review forms provide scoped CSRF tokens;
  runtime confirmation remains blocked.
- No real customer details, access codes, staff secrets, credentials or private
  mail addresses were used.

## B1 conclusion

The source review supports the intended security boundaries, but the matrix is
BLOCKED until the local PHP/MySQL/browser environment is available. This is an
audit-only milestone with no backend remediation commit.

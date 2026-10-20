# Week 7 Customer Regression Report

## Environment and counts

This report is based on integrated `origin/main` at `882c3be`. PHP/MySQL and browser automation were unavailable, so no functional runtime test is claimed as PASS.

| Outcome | Count |
|---|---:|
| PASS | 0 |
| FAIL | 0 |
| BLOCKED | 8 |
| NOT_RUN | 0 |

## Regression matrix

| Test ID | Scenario / input | Expected | Method | Actual | Status | Evidence / fix reference |
|---|---|---|---|---|---|---|
| A4-01 | Home → Shop → Product → Cart → Checkout → Confirmation with seeded data | Real order saves once; cart clears only after success | Browser + PHP/MySQL | Runtime unavailable | BLOCKED | Source flow inspected |
| A4-02 | Same product/variant twice, then different variant | Same variant aggregates; different variant is separate | Browser/session + DB | Runtime unavailable | BLOCKED | `includes/cart.php` inspected |
| A4-03 | Invalid checkout with populated cart | Errors display; cart and valid inputs remain | Browser + PHP POST | Runtime unavailable | BLOCKED | `checkout.php` inspected |
| A4-04 | Valid/invalid guest code in returning/private session | Only valid code elevates session; no foreign PII | Private browser sessions + DB | Runtime unavailable | BLOCKED | `order_access.php`/`orders.php` inspected |
| A4-05 | Staff publishes mock-up; customer requests changes; revision approved | Feedback/history/status persist | Two roles + MySQL | Runtime unavailable | BLOCKED | `mockup.php`, `mockup-action.php`, `review.php` inspected |
| A4-06 | Multi-item approval | Order confirms only after every applicable item is approved | DB workflow | Runtime unavailable | BLOCKED | `review.php` approval count inspected |
| A4-07 | Confirmation/order history after status changes | Correct statuses; no unauthorized sensitive data | Browser + DB | Runtime unavailable | BLOCKED | Authorization paths inspected |
| A4-08 | Base Version scan | No forbidden frameworks/iframe/external forms; page count remains in scope | Static source scan | No forbidden frontend dependency or iframe matches; served page count/runtime behavior remains unverified | BLOCKED | Static scan executed; PHP/browser unavailable |

## Implemented fixes

- Header search now submits the parameter Shop consumes; Shop accepts legacy `q` safely.
- Header cart count is session-backed.
- Homepage style cards preserve category intent.
- Product colour is restricted to the DB-backed colour, quantity is capped at shared max 99, product name is no longer hard-coded, and cart CSRF is included.
- Cart add/update/remove/clear now use the existing CSRF helper and remain POST-only.
- Checkout and mock-up forms restore native validation and retain server validation; checkout errors have accessible associations.
- Valid mock-up submission disables the submit button to reduce repeat clicks.
- Footer brand mark is corrected to F2B.

## Final Week 7 corrections

- Header session setup now runs before HTML output, preventing navbar cart-count session/header warnings.
- Cart add now verifies the submitted colour against the selected product’s normalized database colour; invalid colours do not mutate the session cart.
- Checkout address validation now trims the textarea value with `setCustomValidity()` so whitespace-only addresses are rejected client-side as well as server-side.

## Handoff and deferred execution

- Run all blocked workflows using disposable local data and a configured PHP/MySQL/browser environment.
- Capture sanitized viewport screenshots and console results.
- Run `php -l` on changed PHP files when PHP CLI is available.
- Do not claim local acknowledgement email delivery without actual permitted local-mail evidence.

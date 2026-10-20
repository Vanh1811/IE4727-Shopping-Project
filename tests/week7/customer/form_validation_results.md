# Week 7 Customer Form Validation Results

## Environment

PHP CLI, MySQL/MariaDB and browser automation were unavailable. No runtime PASS is claimed. Source-level changes are recorded as implementation evidence; execute the cases in the local web environment before final coursework submission.

## Checkout fields

| Test ID | Field / input | Expected client behavior | Expected PHP behavior | Actual / status | Evidence / fix reference |
|---|---|---|---|---|---|
| A2-01 | Blank full name | Required/non-whitespace validation | PHP returns full-name error | Not executed; BLOCKED | `checkout.php` now has required, minlength, maxlength, pattern and error association |
| A2-02 | One/161-character name | Min/max rejection | PHP rejects bounds | Not executed; BLOCKED | Same field; server validation retained |
| A2-03 | Invalid email | Email type rejects | PHP rejects invalid email | Not executed; BLOCKED | Native `type=email`; server validation retained |
| A2-04 | Oversized email | Max length rejects | PHP rejects over 254 chars | Not executed; BLOCKED | `maxlength=254` |
| A2-05 | Invalid phone `abc<script>` | Pattern rejects | PHP rejects invalid characters | Not executed; BLOCKED | Phone pattern added; server validation retained |
| A2-06 | Valid formatted phone `+65 9123 4567` | Accepted | PHP accepts formatted phone | Not executed; BLOCKED | Pattern aligned with current validator |
| A2-07 | Invalid postal code | Required/min/max/pattern rejects | PHP rejects invalid code | Not executed; BLOCKED | Postal constraints added |
| A2-08 | Blank/whitespace address | Required/non-whitespace rejects | PHP rejects blank/control chars | Not executed; BLOCKED | Address pattern added |
| A2-09 | 501-character address | Max length rejects | PHP rejects overlong address | Not executed; BLOCKED | `maxlength=500` retained |
| A2-10 | 1,001-character instructions | Max length rejects | PHP rejects overlong text | Not executed; BLOCKED | `maxlength=1000` retained |
| A2-11 | Malicious HTML in fields | No script execution | Escaped output/server checks | Not executed; BLOCKED | Escaping retained; browser unavailable |
| A2-12 | Array submitted in scalar field | Browser not involved | PHP scalar reader rejects safely | Not executed; BLOCKED | `f2bOrderReadInput()` inspected |
| A2-13 | Server-rejected valid values | Inputs remain after response | PHP returns values and errors | Not executed; BLOCKED | `$checkoutValues` repopulation retained |
| A2-14 | Keyboard submit | Native validation is reachable | Normal POST reaches PHP | Not executed; BLOCKED | `novalidate` removed; browser unavailable |

## Product, cart, guest and review negatives

| Test ID | Input / steps | Expected | Actual / status | Evidence / fix reference |
|---|---|---|---|---|
| A2-15 | Empty product size | Client/PHP reject; no cart mutation | Not executed; BLOCKED | `js/main.js` and `f2bCartValidateSize()` |
| A2-16 | Product quantity 0 | Reject | Not executed; BLOCKED | PHP validator inspected |
| A2-17 | Product quantity 100 | UI caps at 99; PHP rejects 100 | Not executed; BLOCKED | Shared max wired in product/JS |
| A2-18 | Fake product ID | No cart mutation | Not executed; BLOCKED | DB lookup inspected |
| A2-19 | Missing cart CSRF | Server rejects state change | Not executed; BLOCKED | `cart-action.php` validates `cart` scope |
| A2-20 | Forged cart key | No unauthorized session mutation | Not executed; BLOCKED | Key parser + CSRF guard inspected |
| A2-21 | Missing checkout CSRF | Server rejects order creation | Not executed; BLOCKED | Existing checkout CSRF retained |
| A2-22 | Invalid guest code | No session elevation/order data | Not executed; BLOCKED | `order_access.php` and `orders.php` inspected |
| A2-23 | Missing review decision | Client prevents POST | Not executed; BLOCKED | `js/mockup.js` and required radio inspected |
| A2-24 | Empty change request | Required conditional field rejects | Not executed; BLOCKED | JS custom validity + PHP review validation inspected |
| A2-25 | Repeated review click | Button disables after valid submission; stale revision rejected | Not executed; BLOCKED | `js/mockup.js` and `review.php` inspected |

## CSRF integration

Product add, cart update/remove/clear, checkout, guest order access, and mock-up review all use the existing `f2bCsrfToken()`/`f2bCsrfValidate()` helper. No second CSRF system was introduced.

## Final Week 7 corrections

- Session initialization now occurs in `includes/header.php` before any HTML output; `includes/navbar.php` reuses the existing cart helper without starting a second session.
- Cart session initialization now delegates to `f2bOrderAccessStartSession()`, keeping cart, checkout and guest-order access on the same secure cookie settings.
- `f2bCartAdd()` now compares the normalized submitted colour with the product colour loaded from MySQL and rejects unsupported colours without changing the session cart.
- The ineffective address `<textarea pattern>` was removed. `checkout.php` now uses `trim()` and `setCustomValidity()` to reject whitespace-only addresses while retaining PHP validation and `aria-invalid` handling.

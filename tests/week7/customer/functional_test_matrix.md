# Week 7 Customer Functional Test Matrix

## Environment

- Branch: `feature/week7-customer-qa`, based on integrated `origin/main` SHA `882c3be` (PRs #9, #10 and #11 present).
- PHP CLI/MySQL runtime/browser automation: unavailable in this environment.
- Test data: fictional/sanitized only; no credentials, access codes, cookies, or live mail addresses recorded.
- Runtime status rule: unavailable runtime cases are `BLOCKED` or `NOT_RUN`; source inspection is not reported as a runtime PASS.

## Matrix

| Test ID | Feature | Prerequisites | Input / steps | Expected | Actual | Status | Evidence / fix reference |
|---|---|---|---|---|---|---|---|
| A1-01 | Homepage hero | PHP server | Open `index.php`; activate Shop Now | Shop opens | Not executed | BLOCKED | Browser/PHP unavailable; link inspected in `index.php` |
| A1-02 | Homepage arrivals | PHP/MySQL | Activate New Arrivals product | Correct product ID opens | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-03 | Homepage top sellers | PHP/MySQL | Activate Top Selling product | Correct product ID opens | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-04 | Homepage categories | PHP server | Activate each style card | Shop opens with relevant category | Not executed | BLOCKED | Category query links fixed in `index.php` |
| A1-05 | Header search match | PHP/MySQL | Search `Studio` in header | Matching products and query remain visible | Not executed | BLOCKED | Header now submits `search`; compatibility `q` is accepted in `shop.php` |
| A1-06 | Header search no match | PHP/MySQL | Search `zz-no-product` | Empty state appears without warning | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-07 | Shop category | PHP/MySQL | Select T-Shirts and apply | Only matching category products appear | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-08 | Shop min/max price | PHP/MySQL | Set min `30`, max `60` | Inclusive price range applies | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-09 | Shop combined filters | PHP/MySQL | Search + category + price + sort | All filters apply and inputs persist | Not executed | BLOCKED | Query preservation inspected in `shop.php` |
| A1-10 | Shop invalid range | PHP server | Set min greater than max | Clear notice and no misleading results | Not executed | BLOCKED | Validation branch inspected in `shop.php` |
| A1-11 | Product valid ID | PHP/MySQL | Open `product.php?id=1` | Product details and controls render | Not executed | BLOCKED | Browser/MySQL unavailable |
| A1-12 | Product invalid ID | PHP server | Open `product.php?id=999999` | Safe not-found state renders | Not executed | BLOCKED | Fallback inspected in `product.php` |
| A1-13 | Product gallery | Browser | Activate each thumbnail | Main image and alt text update | Not executed | BLOCKED | Browser unavailable |
| A1-14 | Product selection POST | Browser/PHP/MySQL | Choose size/colour and submit Add to Cart | Selected fields reach `cart-action.php` | Not executed | BLOCKED | Hidden fields and JS inspected in `product.php`/`js/main.js` |
| A1-15 | Product quantity lower bound | Browser | Decrease from 1 | Quantity stays at 1 | Not executed | BLOCKED | Browser unavailable |
| A1-16 | Product quantity upper bound | Browser | Increase past 99 | Quantity stops at 99 | Not executed | BLOCKED | Shared max wired in `product.php`/`js/main.js` |
| A1-17 | Cart same variant | PHP/MySQL/session | Add same variant twice | One line aggregates quantity | Not executed | BLOCKED | Cart helper inspected; runtime unavailable |
| A1-18 | Cart distinct variants | PHP/MySQL/session | Add same product with two sizes | Separate lines remain distinct | Not executed | BLOCKED | Runtime unavailable |
| A1-19 | Cart update/remove | PHP/session | Submit update and remove actions | Only requested item/session state changes | Not executed | BLOCKED | CSRF fields/POST guard added; runtime unavailable |
| A1-20 | Cart clear | PHP/session | Submit Clear cart | Session cart becomes empty | Not executed | BLOCKED | CSRF field/POST guard added; runtime unavailable |
| A1-21 | Cart totals | PHP/MySQL | Compare item prices, subtotal, discount, delivery, total | Values match DB-backed calculation | Not executed | BLOCKED | MySQL unavailable; `includes/cart.php` inspected |
| A1-22 | Cart refresh persistence | PHP/session | Refresh and reopen cart | Session items remain | Not executed | BLOCKED | Browser/PHP unavailable |
| A1-23 | Empty checkout | PHP/session | Open checkout with empty cart | Redirect to cart with message | Not executed | BLOCKED | Redirect inspected in `checkout.php` |
| A1-24 | Successful checkout | PHP/MySQL | Submit valid fictional customer details | Order saves once, cart clears, confirmation opens | Not executed | BLOCKED | PHP/MySQL unavailable |
| A1-25 | Checkout rejection | PHP/MySQL | Submit invalid field with populated cart | Errors and values remain; cart is retained | Not executed | BLOCKED | Server value retention inspected in `checkout.php` |
| A1-26 | Confirmation access | PHP/MySQL/session | Open valid, invalid and foreign order IDs | Only authorized order is shown | Not executed | BLOCKED | `order_access.php` guard inspected; runtime unavailable |
| A1-27 | Guest order unlock | PHP/MySQL/session | Use valid and invalid fictional guest codes | Valid session elevates; invalid code reveals nothing | Not executed | BLOCKED | `orders.php`/`order_access.php` inspected |
| A1-28 | Mock-up states | PHP/MySQL/session | Open PENDING, READY, CHANGES, APPROVED items | State-specific UI renders | Not executed | BLOCKED | `mockup.php` state branches inspected |
| A1-29 | Mock-up review persistence | PHP/MySQL/session | Request changes, refresh, then approve revision | Review history/status persists | Not executed | BLOCKED | `mockup-action.php`/`review.php` inspected |

## A1 findings and fixes

- Header search parameter was mismatched with Shop and cart count was hard-coded.
- Homepage style cards did not preserve a meaningful category filter.
- Footer brand mark showed `T` instead of `F2B`.
- Runtime evidence remains blocked until PHP, MySQL and a browser are available.

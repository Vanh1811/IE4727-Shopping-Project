# Week 7 Customer Browser and Accessibility Review

## Environment and limitation

PHP/MySQL and browser automation were unavailable. No screenshots or console results were captured. Every viewport result is `NOT_RUN`; source review is not a rendered-browser PASS.

## Viewport matrix

| Page | 1440px | 1024px | 768px | 375px | Review notes |
|---|---|---|---|---|---|
| Home | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Shared responsive layout; category links fixed |
| Shop | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Filter/search inputs preserve values |
| Product | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Selection/quantity/CSRF fixes applied |
| Cart | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | POST forms now carry CSRF tokens |
| Checkout | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Native validation and field associations restored |
| Confirmation | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Authorization is present; runtime not verified |
| My Orders | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | Authorized order table/access form present |
| Mock-up | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN | State-specific review UI present |

## Checklist

| Test ID | Check | Expected | Actual / status | Evidence |
|---|---|---|---|---|
| A3-01 | Page titles/headings | Meaningful title and main heading on every page | Source inspected; browser NOT_RUN | `includes/header.php` and page files |
| A3-02 | Focus visibility | Keyboard focus is visible for links, buttons, inputs and selects | Source inspected; browser NOT_RUN | `css/style.css` focus-visible rules |
| A3-03 | Image alt text | Product/order/mock-up images describe content; decorative art is hidden | Source inspected; browser NOT_RUN | Customer PHP templates |
| A3-04 | Checkout errors | Error IDs are associated and `aria-invalid` updates | Implemented; browser NOT_RUN | `checkout.php` |
| A3-05 | Review controls | Radio decision and conditional textarea are keyboard reachable | Source inspected; browser NOT_RUN | `mockup.php`, `js/mockup.js` |
| A3-06 | Cart controls | Remove/update/clear are buttons in POST forms | Source inspected; browser NOT_RUN | `cart.php` |
| A3-07 | Orders table | Caption and scoped column/row headings exist | Source inspected; browser NOT_RUN | `orders.php` |
| A3-08 | Overflow | Long tables/content remain usable at target widths | Cannot render | Browser unavailable |
| A3-09 | Console/notices | No uncaught JS/PHP warnings | Cannot execute | Browser/PHP unavailable |
| A3-10 | Brand consistency | Header/footer use F2B mark | Footer fixed; browser NOT_RUN | `includes/footer.php`, `includes/navbar.php` |

## Unresolved verification

- Capture sanitized screenshots and browser console evidence in a local PHP/MySQL setup.
- Verify no horizontal overflow at all four widths.
- Run keyboard traversal and screen-reader spot checks.

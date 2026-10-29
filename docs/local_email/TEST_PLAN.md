# Local order receipt acceptance plan

## Environment and execution record

Observed on 29 October 2026 on Windows: XAMPP PHP CLI 8.0.30 at
`D:\xampp\php\php.exe`; `mysqli` and `mail()` are available; PHP loaded
`D:\xampp\php\php.ini`; native SMTP configuration is `localhost:25`, with
no `sendmail_path`. Local TCP checks found ports 80 and 3306 open and 1025
and 8025 closed. Mailpit was not found on PATH. No checkout, DB row, or inbox
message was created as part of these checks.

PHP syntax checks run:

```text
php -l includes/mail.php     PASS — No syntax errors detected
php -l checkout.php         PASS — No syntax errors detected
php -l confirmation.php     PASS — No syntax errors detected
git diff --check            PASS — no whitespace errors in each milestone
```

`git diff --check` reported Git's line-ending conversion warning for new LF
files on this Windows checkout; it did not report whitespace errors.

The live acceptance tests below require the student's own XAMPP session and a
loopback-only mail server. `BLOCKED` means prerequisites for that runtime test
were not present here. `NOT_RUN` means the stated behavior was not exercised;
source review is not presented as runtime evidence.

## Acceptance matrix

| Test ID | Prerequisites/Input | Expected Result | Test Method | Actual Result | Status | Sanitized Evidence | Related Fix/Commit |
|---|---|---|---|---|---|---|---|
| E01 | Local DB; feature flag disabled; fake checkout | Order saved; no mail call; status `disabled` | Place a local browser order and inspect DB/inbox | Not executed; browser order not placed | BLOCKED | PHP CLI available; no order evidence | M1 `6c70a1a`; M2 `82a93a5` |
| E02 | Exact allowlisted local recipient; loopback receiver running | One message accepted and visible in the student's own local inbox | Place one order; inspect inbox and DB stamp | Not executed; receiver ports 1025/8025 closed | BLOCKED | No message or customer data captured | M1 `6c70a1a`; M3 `10f8a7e` |
| E03 | Order with two items, distinct variants and quantities | Every name, size, colour, unit price, quantity, and line total matches saved rows | Compare local inbox with `order_items` for test order ID | Not executed | BLOCKED | No test order ID or item data captured | M1 `6c70a1a` |
| E04 | Test order with discount and delivery fee | Stored subtotal, discount, fee, and total appear to two decimals | Compare inbox with `orders` test row | Not executed | BLOCKED | No order totals captured | M1 `6c70a1a` |
| E05 | First authorized confirmation after checkout | One-time guest code and independent mail status both visible | Complete browser checkout and inspect first response | Not executed in browser | BLOCKED | Source implementation has separate code and mail panels; not runtime proof | M2 `82a93a5` |
| E06 | Refresh that confirmation page | Code is not shown again; same order-scoped mail status remains; no second message appears | Refresh and compare inbox count/code visibility | Not executed | BLOCKED | Source keeps status keyed to the matching order ID; no confirmation mail call; runtime not exercised | M2 `82a93a5`; follow-up `9f18013` |
| E07 | Request another order ID without session authorization | No customer/order data, access code, or mail status shown | Open unauthorized confirmation URL in a clean session | Not executed in browser | BLOCKED | Authorization check precedes order loading in source; not runtime proof | M2 `82a93a5` |
| E08 | Empty, malformed, or non-allowlisted configured recipient | No mail call; safe `disabled` result | Run disposable checkout with invalid local mail environment | Not executed | BLOCKED | No transport call evidence | M1 `6c70a1a` |
| E09 | Public address in checkout or recipient configuration | Checkout address is ignored; external-domain config fails closed | Disposable environment; inspect mail receiver and result | Not executed | BLOCKED | Source pins recipient to allowlisted `.test`, `.localhost`, `.local`, or localhost domain; runtime still needed | M1 `6c70a1a` |
| E10 | Local receiver stopped during checkout | Order remains committed; result is `failed`; no email stamp | Stop receiver, place order, inspect order and `email_sent_at` | Not executed | BLOCKED | Receiver was not available to create a test order | M1 `6c70a1a` |
| E11 | Existing test order has `email_sent_at` set | Helper returns `already_sent`; no new message | Invoke helper via a controlled local order flow and check inbox count | Not executed | BLOCKED | No DB test order or inbox evidence | M1 `6c70a1a` |
| E12 | Name/item text includes CR/LF, HTML, and angle brackets | No header injection; message remains plain text; no HTML execution | Submit controlled values and inspect raw local message | Not executed | BLOCKED | Source strips controls and uses fixed headers; dynamic injection behavior not exercised | M1 `6c70a1a` |
| E13 | Product catalogue data changed after order creation | Receipt still reflects saved `order_items` snapshot | Change product name/price in disposable DB, then generate receipt | Not executed | BLOCKED | Receipt query selects only persisted `orders`/`order_items`; runtime not exercised | M1 `6c70a1a` |
| E14 | Repeat a valid checkout POST | Determine whether duplicate POST can create two different orders | Submit same valid POST twice in disposable local environment | Not dynamically tested; code has no checkout idempotency key, so repeated valid POSTs may create two orders | NOT_RUN | Finding documented; normal 303 refresh does not prevent a repeated POST | M3 `10f8a7e` |
| E15 | PHP CLI, XAMPP, DB, and local mail access unavailable | Mark unavailable runtime checks `BLOCKED`/`NOT_RUN`, never PASS | Inspect actual tool and port availability | Partial environment: PHP CLI available; local ports 80/3306 open; SMTP receiver unavailable | BLOCKED | PHP 8.0.30 observed; 1025/8025 closed; no inbox evidence | M3 `10f8a7e` |
| E16 | Source compatibility review | PHP/mysqli/MySQL/HTML/CSS only; no AJAX/JSON API/cloud mail dependency | Review scoped changed code and dependencies | No AJAX, JSON API, Composer dependency, cloud mail API, or external recipient path introduced | PASS | Scoped source review; no runtime claim | M1 `6c70a1a`; M2 `82a93a5` |

## Illustrative receipt format

This is a format example only, using invented data. It is not a captured email
or evidence that delivery succeeded.

```text
F2B SHOP
ORDER ACKNOWLEDGEMENT

Hello Example Customer,

Thank you for shopping with F2B SHOP.
We have received your order. Your design mock-up will be prepared for review.

Order number: #12345
Order date: 29 October 2026
Status: ORDER PLACED

ORDERED ITEMS
1. Example Cotton Tee
   Size: M | Colour: Navy
   Unit price: $18.00 | Quantity: 2 | Line total: $36.00

Subtotal: $36.00
Discount: -$0.00
Delivery fee: $5.00
TOTAL: $41.00

Next step: our team will publish a mock-up for your approval or change requests.
Thank you,
F2B SHOP
```

## Current totals

- PASS: 1 source-compatibility review.
- BLOCKED: 14 tests requiring runtime, database, transport, or browser evidence.
- NOT_RUN: 1 dynamic repeated-POST test; the static review found no
  idempotency key, and that risk is documented in the setup guide.
- FAIL: 0.

The last checkout mail status remains in session with its order ID so refreshes
can show the original `sent`, `already_sent`, `disabled`, or `failed` result.
Confirmation reads it only after session authorization and an exact order-ID
match. A missing status with no DB handoff stamp is described as unrecorded,
not falsely relabeled disabled or failed. Rendering never calls the mail
helper.

Runtime acceptance remains pending until the student's own local checkout,
database, confirmation page, and local inbox have been exercised. The setup
steps are in [README.md](README.md).

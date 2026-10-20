# Week 7 Mock-up, Staff Workflow and Local Email Results

## Environment and mail configuration

The workflow was reviewed against integrated `main` at `3110b2a`. PHP CLI,
MySQL/MariaDB and browser automation were unavailable, so no database workflow
or email delivery case is claimed as PASS.

The environment variables `F2B_LOCAL_MAIL_ENABLED`, `F2B_LOCAL_MAIL_TO` and
`F2B_STAFF_PASSWORD_HASH` were not set. No email attempt was made and no
external recipient was contacted. A real delivery test remains required only
with the student's permitted local web-server email account.

## Workflow matrix

| Test ID | Prerequisites / input | Expected before -> after | Actual result | Method | Status | Evidence / fix commit |
|---|---|---|---|---|---|---|
| B3-01 | New order created through checkout | `ORDER_PLACED`; staff-only dashboard can view it | `f2bOrderCreate()` sets `ORDER_PLACED`; dashboard requires staff authentication; DB state not executable | Source review + PHP/MySQL | BLOCKED | `includes/order.php`, `admin-orders.php`; runtime unavailable |
| B3-02 | Authenticated staff publishes one valid local preview per order item | Item `PENDING` -> `READY_FOR_REVIEW`; order -> `DESIGN_REVIEW` | Transaction locks order/item, validates mapping and local image path, then updates both records | Source review + disposable DB | BLOCKED | `includes/admin_order.php`; runtime unavailable |
| B3-03 | Customer with valid guest session requests changes with a valid comment | Mock-up `READY_FOR_REVIEW` -> `CHANGES_REQUESTED`; review row saved | Owner access, CSRF, current revision and required comment are checked before insert/update | Source review + browser/DB | BLOCKED | `includes/review.php`, `mockup-action.php`; runtime unavailable |
| B3-04 | Staff publishes a revision after customer feedback | Existing review remains; mock-up revision increments and returns to `READY_FOR_REVIEW` | Existing mock-up is updated only for `CHANGES_REQUESTED`; review rows are not deleted | Source review + disposable DB | BLOCKED | `includes/admin_order.php`; runtime unavailable |
| B3-05 | Customer approves latest revision | Mock-up -> `APPROVED`; one-item order -> `CONFIRMED` | Review locks current revision and confirms only when approved mock-ups equal order items | Source review + browser/DB | BLOCKED | `includes/review.php`; runtime unavailable |
| B3-06 | Multi-item order with only some approved | Order remains in review until every applicable mock-up is approved | Approval count compares all order items to approved mock-ups | Source review + disposable DB | BLOCKED | `includes/review.php`, `includes/admin_order.php`; runtime unavailable |
| B3-07 | Invalid direct status jump, stale revision, repeated decision or forged item/order mapping | Mutation rejected and related rows remain unchanged | Staff transition allowlist, `FOR UPDATE`, revision match and order-item mapping checks are present | Source review + failure injection | BLOCKED | `includes/admin_order.php`, `includes/review.php`; runtime unavailable |
| B3-08 | Staff advances confirmed order | `CONFIRMED` -> `PROCESSING` -> `COMPLETED`; invalid transitions rejected | Status helper permits only those two transitions and rechecks all mock-ups approved | Source review + disposable DB | BLOCKED | `includes/admin_order.php`; runtime unavailable |
| B3-09 | Customer refreshes confirmation after acknowledgement handling | No automatic duplicate mail; `email_sent_at` reflects successful local delivery | Mail helper checks `email_sent_at` under row lock and confirmation only reads stored/session status; mail server unavailable | Source review + local mail server | BLOCKED | `includes/mail.php`, `checkout.php`, `confirmation.php`; delivery unverified |
| B3-10 | Mail disabled, mail failure, or DB state-update failure | Order remains saved; UI does not falsely claim sent | Disabled and failed results are explicit; no live delivery or failure simulation was possible | Source review + local mail server | BLOCKED | `includes/mail.php`, `confirmation.php`; runtime unavailable |

## Static workflow observations

- Staff order and mock-up pages/actions are protected by the configured staff
  session and scoped CSRF tokens.
- Preview paths are restricted to existing repository-local product images;
  external URLs and traversal paths are rejected.
- Mock-up reviews are append-only and stale revisions cannot be submitted after
  the current row is no longer `READY_FOR_REVIEW`.
- Customer approval changes the order to `CONFIRMED` only when every order item
  has an approved mock-up. Staff then controls the two production transitions.
- `f2bOrderSendLocalAcknowledgement()` never uses the customer email as the
  recipient and is disabled unless the local-mail environment flag and a
  configured recipient are present.

## Counts

| Outcome | Count |
|---|---:|
| PASS | 0 |
| FAIL | 0 |
| BLOCKED | 10 |
| NOT_RUN | 0 |

## Required local execution

Use a disposable seeded database, a dedicated local staff password hash, and
the student's own permitted local mail account. Record sanitized order IDs,
before/after statuses, review row counts, revision numbers, and mail delivery
proof separately. Do not treat the confirmation screen alone as delivery
evidence.

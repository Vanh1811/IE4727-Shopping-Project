# Week 7 Database, Transaction and Financial Integrity Results

## Environment and setup

Static review was performed from integrated `main` at `3110b2a` on branch
`feature/week7-backend-qa`.

The intended disposable setup is:

1. Import `database/schema.sql` into a disposable `f2b_shop` database.
2. Import `database/seed.sql`.
3. For an existing Week 5 copy, apply
   `database/migrations/002_week6_workflow.sql` without importing the fresh
   schema over existing data.
4. Use only sanitized disposable order identifiers in SQL observations.

PHP CLI and MySQL/MariaDB are unavailable in this environment, so no database
or transaction runtime case is claimed as PASS. No project database was
modified.

## Results matrix

| Test ID | Prerequisites / input | Expected | Actual result | Method | Status | Evidence / fix commit |
|---|---|---|---|---|---|---|
| B2-01 | Disposable database; import `schema.sql` then `seed.sql` | All catalogue, order, mock-up and review tables load with required keys | Schema and seed files were inspected; database import could not run | Static SQL review + MySQL CLI | BLOCKED | `database/schema.sql`, `database/seed.sql`; runtime unavailable |
| B2-02 | Existing Week 5 disposable database; apply migration 002 | Existing orders/items remain and workflow objects/columns are added idempotently | Migration uses conditional column/index checks and additive `CREATE TABLE IF NOT EXISTS`; no live row-count verification | Static SQL review + MySQL CLI | BLOCKED | `database/migrations/002_week6_workflow.sql`; runtime unavailable |
| B2-03 | Valid cart with one or more items and valid checkout values | One order and expected order items commit with snapshot prices and calculated totals | `f2bOrderCreate()` begins a transaction, inserts the order/items with prepared statements, then commits | Source review + disposable DB | BLOCKED | `includes/order.php`, `includes/cart.php`; runtime unavailable |
| B2-04 | Simulated order-item prepare/execute failure | Entire order transaction rolls back; cart remains available | Every item failure path closes the statement and calls rollback; no runtime failure injection was possible | Source review + disposable DB | BLOCKED | `includes/order.php`; runtime unavailable |
| B2-05 | Repeated checkout POST/double submit with the same session | No accidental duplicate customer order | CSRF is present, but no server-side checkout idempotency key was found; runtime race test was not possible | Source review + browser/DB | BLOCKED | `checkout.php`, `includes/order.php`; duplicate-submit hardening remains unverified |
| B2-06 | Invalid database connection or invalid product/order ID | No bogus success, SQL details or silent cart loss | Database helper exposes only generic errors; cart loading now preserves the session cart when the DB is unavailable | Static source review; runtime unavailable | BLOCKED | `includes/db.php`, `includes/cart.php`; fix in this B2 commit |
| B2-07 | Fresh and migrated schemas with orphan/malformed rows | Foreign keys, unique mock-up-per-item and amount/quantity constraints hold | Required foreign keys, unique keys and `CHECK` constraints are present in fresh schema and workflow migration | Static SQL review + disposable DB | BLOCKED | `database/schema.sql`, `database/migrations/002_week6_workflow.sql`; runtime unavailable |
| B2-08 | Multi-item checkout with seeded products | Each item preserves product name, variant, unit price and line total; order totals reconcile | Source snapshots item values from cart/database and calculates line totals before insert; SQL reconciliation was not executed | Source review + SQL queries | BLOCKED | `includes/cart.php`, `includes/order.php`; runtime unavailable |
| B2-09 | Status update and invalid transition attempts | Only allowed persisted transitions succeed | Staff helper locks the order, validates `CONFIRMED -> PROCESSING -> COMPLETED`, checks all mock-ups approved, and updates with prepared SQL | Source review + disposable DB | BLOCKED | `includes/admin_order.php`; runtime unavailable |

## Static integrity observations

- `f2bOrderCreate()` uses one transaction for the order and all order items;
  failed prepares, failed executions and failed commits roll back.
- `order_items` stores product name, size, colour, unit price, quantity and
  line total snapshots rather than relying on a later product lookup.
- The fresh schema and Week 6 migration define foreign keys for orders/items,
  orders/mock-ups and mock-ups/reviews, plus the unique mock-up-per-order-item
  constraint.
- `f2bCartLoadItems()` now returns without removing session entries when the
  database object is unavailable or its connection cannot be pinged. This
  prevents a temporary database outage from silently erasing a guest cart.
- No schema rewrite or new migration was needed for this B2 correction.
- The source review did not find a checkout idempotency key. Duplicate-submit
  behavior must be tested in the local environment before Base Version signoff;
  no unsupported runtime PASS is claimed.

## Counts

| Outcome | Count |
|---|---:|
| PASS | 0 |
| FAIL | 0 |
| BLOCKED | 9 |
| NOT_RUN | 0 |

## Cleanup and handoff

No disposable database was created because MySQL/MariaDB was unavailable. Run
the setup and failure-injection cases with a separate local database before
claiming transaction, migration, rollback or duplicate-submit success.

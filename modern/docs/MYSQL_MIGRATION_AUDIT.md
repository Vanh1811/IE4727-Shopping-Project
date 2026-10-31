# MongoDB to XAMPP MySQL migration audit

> Historical source inventory: this audit records the state before persistence changes. The completed migration and current test results are in [STATUS.md](STATUS.md), [MYSQL_MIGRATION_RESULTS.md](MYSQL_MIGRATION_RESULTS.md), and [PARITY_RESULTS.md](PARITY_RESULTS.md). Mongo names below refer only to the source branch being replaced; they are not setup instructions for this branch.

Audit date: 2026-10-31  
Source branch: `feature/modern-react-express-mongo` at `680da84`  
Migration branch: `feature/modern-express-mysql`

This is the required pre-migration inventory. No persistence implementation has been changed in this audit checkpoint. PHP files and the root `database/` schema/seed/migration are read-only references.

## Baseline verification

| Check | Result | Evidence |
| --- | --- | --- |
| `npm run build` | PASS | Express TypeScript and Vite production bundle built on 2026-10-31. |
| `npm run typecheck` | PASS | Server and client checks passed. |
| `npm run lint` | PASS | Server and client ESLint passed. |
| `npm run test` | PASS | 23 unit/smoke tests passed. These currently test Mongoose query-plan assumptions by name but do not require a live DB. |
| `npm run test:e2e` | PASS (inherited Stage 8 checkpoint) | Six browser smoke checks passed against a local app without persistent MongoDB. They do not exercise populated checkout/staff workflows. |
| `npm run test:api` | BLOCKED | The source branch records `ECONNREFUSED 127.0.0.1:27017`; local port 27017 is currently closed. Its destructive setup is restricted to the exact test DB URI. |
| XAMPP MySQL reachability | PASS (TCP only) | `Test-NetConnection 127.0.0.1 -Port 3306` returned `True`. Credentials, database names, and schema readiness have not been read or changed during this audit. |
| PHP/Base runtime comparison | BLOCKED | The existing parity record says PHP/MySQL/browser checks were unavailable. No PHP or root database command was run. |

The original `npm run build` attempt failed before compilation because the system drive had no free space (`ENOSPC`). Running the same command with npm cache and temporary paths redirected to ignored `modern/node_modules` locations passed. No cache-clearing or database mutation was performed.

## Mongo inventory and replacement plan

| Mongo usage | Current files / behavior | Planned MySQL replacement |
| --- | --- | --- |
| Runtime package and lock data | At audit time `modern/server/package.json` contained `mongoose`, `connect-mongo`; the lockfile contained their transitive driver. | `mysql2/promise` is the sole SQL driver; `mongoose`, `connect-mongo` and MongoDB packages have been removed from the final package manifests and lockfile. |
| Connection configuration | `server/src/config/env.ts`: optional `MONGODB_URI`, production requirement. `server/src/server.ts`: asynchronously connects after starting Express. | Validated `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, with safe loopback defaults only in `.env.example`; await pool startup/readiness and sanitize connection errors. |
| Health/readiness | `server/src/routes/health.ts`: Mongoose connection state; returns 200 only for configured+connected. | `SELECT 1` plus schema/migration readiness from a MySQL health service; preserve `{data,error}` and sanitized 503 state. |
| Express sessions | `server/src/app.ts`: conditional `MongoStore`; without URI Express falls back to `MemoryStore`. Session fields are declared in `server/src/types/express-session.d.ts`. | A MySQL-backed `express-session` store with parameterized writes, expiration, pruning, and session ID rotation. Memory store remains only in isolated unit tests if a session store is injected explicitly. |
| Catalogue records and query building | `models/Category.ts`, `models/Product.ts`; `catalogue/service.ts` builds `FilterQuery` and Mongoose sort objects; DTO expects populated `categoryRef`. | `categories` and `products` tables, parameterized predicates, fixed allowlisted `ORDER BY`, joined category names, DTO mapper. Preserve LIKE `%`/`_` and backslash semantics. |
| Catalogue seed | `scripts/seed.ts` bulk-upserts categories/products with Mongoose; parser is `catalogue/seedParser.ts`; `db-preflight.ts` checks replica-set state. | Guarded non-destructive SQL upsert from the same root seed parser; schema migrations and DB preflight use exact allowlisted database names and loopback host. Never execute root `schema.sql` directly. |
| Cart and current pricing | `cart/service.ts` reads `Product` by legacy ID, validates exact colour/category sizing, and computes integer-cent totals. Cart itself is in Express session. | Parameterized product repository lookups; retain only product ID/variant/quantity in MySQL-backed session; price and totals come from current SQL rows. |
| Checkout transaction and public IDs | `orders/checkout.ts` reads products, makes Mongoose ObjectIds for embedded order items, allocates `Counter` sequence, inserts order snapshots with `Order.startSession().withTransaction()`, catches duplicate-key code 11000. | InnoDB `orders` + `order_items`; one acquired mysql2 connection; order number from auto-increment order ID; unique idempotency key; transaction and duplicate-key recovery. Stable item IDs from `order_items.id`. |
| Guest order access | `models/Order.ts` stores SHA-256 hash/idempotency key; `orders/access.ts` uses SHA-256+timing-safe compare; `routes/orders.ts` reads with Mongoose and `.lean()`. | `orders.access_token_hash CHAR(64)` unique; SQL lookup by order number; never select hash into customer DTO. Preserve generic error, rate limit, authorized numbers in persistent session, and session rotation. |
| Staff order operations | `routes/staff.ts` uses Mongoose queries, `.skip/.limit`, `countDocuments`, item ObjectIds, `findOneAndUpdate`. | Parameterized joins and count pagination; stable numeric order-item IDs; guarded atomic updates and row locks inside MySQL transactions. Preserve staff authorization and DTO allowlist. |
| Mock-up/review workflow | `models/Mockup.ts` stores review subdocuments; route transactions use Mongoose session; item/mockup IDs are ObjectIds. `staff/mockups.ts` resolves local asset paths. | `mockups` unique on `order_item_id`; append-only `mockup_reviews`; numeric stable mockup ID. Lock order/item/mockup rows and enforce expected revision/status in SQL. Retain local asset allowlist. |
| Receipt claim/state | `models/Order.ts`, `mail/receipt.ts`, checkout and confirmation routes use order snapshots, Mongo update claims and email status. SMTP happens after checkout transaction. | Load only committed `orders`/`order_items` snapshots. Atomic SQL claim/status and timestamp. Keep SMTP outside SQL transactions and non-gating; default disabled/loopback-only. |
| Integration setup/cleanup | At audit time `catalogue/api.integration.ts` connected Mongoose, validated a test URI, deleted collections, and created fixtures. | Final harness requires loopback `f2b_shop_modern_test`, a migrated schema and source seed; it creates unique orders and removes only its own order/session rows. It never drops tables or touches the development/Base databases. |
| Unit/API contracts | `app.test.ts` removes `MONGODB_URI`; `seedParser.test.ts` asserts “Mongo query plans”; API integration uses Mongo collection imports. | Unit tests remain DB-independent and cover SQL builder/DTO/validation. Integration tests use mysql2 against only guarded test DB. |
| Runtime/setup docs | `.env.example`; `SETUP_WINDOWS.md`; `API_CONTRACT.md`; `DATA_MAPPING.md`; `BASELINE_PARITY.md`; `STATUS.md`; `PARITY_RESULTS.md` contain Mongo URI, replica-set and Mongo persistence instructions/claims. | Replace current setup/contract/data mapping and status with XAMPP MySQL instructions. Preserve branch-history facts only as labeled historical context; no active Mongo setup instructions. |

## Protected PHP source reviewed

Read-only references reviewed: `database/schema.sql`, `database/f2b_product_seed.sql`, `database/migrations/002_week6_workflow.sql`, `includes/db.php`, `cart.php`, `order.php`, `order_access.php`, `admin_auth.php`, `admin_order.php`, `review.php`, `mail.php`, `checkout.php`, `orders.php`, `confirmation.php`, `admin-orders.php`, and `mockup.php`.

Important source constraints include InnoDB transactions, `DECIMAL(10,2)` money, public auto-increment numeric IDs, `order_items` snapshots, unique mock-up per order item, append-only reviews, row locks for workflow changes, a unique SHA-256 order access hash, exact status transitions, and local-only PHP mail behavior. The Base schema begins with hardcoded `CREATE DATABASE ... f2b_shop` and `USE f2b_shop`; it must never be used as a Modern initialization script.

## Audit boundary

No connection was made to `f2b_shop`; no schema, seed, migration, customer data, or credentials were read from MySQL during the audit. No PHP files changed. The staged implementation followed this audit in the same feature branch.

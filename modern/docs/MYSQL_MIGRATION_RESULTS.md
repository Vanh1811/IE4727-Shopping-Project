# Modern Version MySQL migration results

Date: 2026-10-31  
Branch: `feature/modern-express-mysql`  
Source: `feature/modern-react-express-mongo` at `680da84`

## Delivered

- Modern runtime persistence is XAMPP MySQL/MariaDB through `mysql2/promise`; MongoDB, Mongoose, Mongo-backed sessions, config, model files, and test setup have been removed.
- React 19, Tailwind CSS 3, Vite/TypeScript, Express 5, `/api/v1` endpoints, DTO envelopes, cookie/CSRF handling, local-only disabled-by-default mail, and no-payment behavior remain.
- Development schema: `f2b_shop_modern`. Integration-test schema: `f2b_shop_modern_test`. Both require loopback and exact `NODE_ENV`-matched database names; `f2b_shop` is rejected by scripts/config.
- Modern migrations: `001_initial.sql` (catalogue, orders/snapshots, workflow and sessions), `002_checkout_fingerprint.sql` (checkout key payload identity), `003_mockup_order_item_ownership.sql` (composite item ownership constraint), and `004_receipt_claim_lease.sql` (recoverable atomic receipt claims).
- Catalogue uses the six categories and 64 source products via non-destructive upserts. Order creation stores immutable item snapshots and SHA-256 guest access hashes in one transaction; staff/mock-up/review lifecycle uses guarded SQL transactions and row locks.
- SQL API integration harness requires only the isolated test database, migrated schema and seeded catalogue. It scopes order/session cleanup and restores the catalogue price it uses for snapshot verification.
- The PHP Base Version and root schema/data files are unchanged.

## Verification run

| Command/check | Result |
| --- | --- |
| `npm run build` | PASS |
| `npm run typecheck` | PASS |
| `npm run lint` | PASS |
| `npm test` | PASS — 30 passed, 0 failed |
| `npm run test:e2e -- --timeout=15000` | PASS — 6 passed using local Chromium 1248 |
| `npm run test:api` (`NODE_ENV=test`, `DB_NAME=f2b_shop_modern_test`) | BLOCKED — test DB readiness failed before API assertions because local MySQL authentication/schema is unavailable; 4 groups did not run |
| `npm run db:init` (`f2b_shop_modern`) | BLOCKED — `Access denied for user 'root'@'localhost' (using password: NO)`; no schema created |
| `npm run db:seed` (`f2b_shop_modern`) | BLOCKED — same XAMPP authentication rejection; no catalogue rows written |
| `npm run db:preflight` | BLOCKED — TCP listener is reachable but the available MySQL credentials are rejected |
| PHP/MySQL differential browser comparison | BLOCKED — no live legacy comparison session available |
| Local SMTP acceptance | BLOCKED — no approved local receiver configured; no message sent |

The checks were run with npm cache/temp redirected under ignored `modern/node_modules` paths because the system drive initially lacked free space. TypeScript commands used `NODE_OPTIONS=--max-old-space-size=768`. No DB initialization, seed, test fixture, customer/order copy, or Base Version write succeeded or was attempted without valid credentials.

## Stage commits pushed

- Audit: `3f88fc9` — `docs(modern): audit Mongo to MySQL migration and define parity`
- Stage A foundation: `979ae2a` — `feat(modern): introduce MySQL schema and session foundation`
- Stage A verification: `75583c1` — `docs(modern): record MySQL stage A verification`
- Stage B catalogue: `a41629c` — `feat(modern): migrate catalogue reads and seed to MySQL`
- Stage C checkout/orders: `e795366` — `refactor(modern): port checkout and order access to MySQL`
- Stage D staff/mock-ups: `227d2b7` — `refactor(modern): port staff mockup workflow to MySQL`
- Stage E local receipt: `a57e166` — `refactor(modern): preserve local receipts in MySQL`
- Stage F cleanup/docs: `5877236` — `test(modern): remove Mongo and finalize MySQL migration`.

All commits were pushed only to `feature/modern-express-mysql`. No pull request was created or merged.

## Windows handoff

Follow [SETUP_WINDOWS.md](SETUP_WINDOWS.md). Set XAMPP credentials in ignored `modern/server/.env`, initialize/seed only `f2b_shop_modern`, and set `NODE_ENV=test` plus `DB_NAME=f2b_shop_modern_test` in a separate PowerShell process before test DB setup and `npm run test:api`. Persisted workflow acceptance remains required after valid local credentials are configured.

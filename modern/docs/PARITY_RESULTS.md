# Modern Version parity results

Date: 2026-10-31  
Branch: `feature/modern-express-mysql`

## Verification summary

| Area | Result | Evidence |
| --- | --- | --- |
| Build, types and lint | PASS | Final cleanup checkpoint: `npm run build`, `npm run typecheck`, and `npm run lint` passed. |
| Unit tests | PASS | `npm test`: 29 passed, 0 failed. Covers catalogue SQL filters, integer-cent conversion, sessions, checkout validation/idempotency fingerprint, staff state transitions, mock-up validation, guest hashing, and receipt safeguards. |
| Browser tests | PASS | `npm run test:e2e -- --timeout=15000`: 6 Chromium checks passed against local Modern UI. Checks home assets, nested route refresh, empty cart, order lookup privacy, unconfigured staff message, and keyboard skip link. These smoke checks do not create orders or prove MySQL-backed workflows. |
| XAMPP connection | PARTIAL | Port `127.0.0.1:3306` accepts TCP connections. Authentication with the available `root`/empty-password settings is rejected (`Access denied ... using password: NO`). No development or test database was initialized or seeded. |
| SQL API/customer/staff integration | BLOCKED | `npm run test:api` uses only `f2b_shop_modern_test`, but readiness is unavailable because no valid XAMPP credentials/schema are configured. Its four integration groups did not reach API assertions. |
| Seeded catalogue and transactional workflows | BLOCKED | `db:seed`, order snapshot/rollback/idempotency, guest authorization, staff/mock-up lifecycle, and SQL receipt claims require valid XAMPP credentials and migrated schemas. No DB writes occurred. |
| Visual comparison with PHP | BLOCKED | No PHP/MySQL browser runtime was available for matching populated page screenshots. Historical source-branch screenshots are not evidence of current SQL data parity. |
| Local receipt acceptance | BLOCKED | No approved loopback SMTP receiver was configured; mail stayed disabled and no message was sent. |

The six browser checks confirm the React application shell and static UI behavior. They do not establish catalogue rendering or persistence parity because no authenticated SQL connection was available. Source data parity is defined by the reviewed PHP/MySQL seed and behavior in [BASELINE_PARITY.md](BASELINE_PARITY.md) and [MYSQL_DATA_MAPPING.md](MYSQL_DATA_MAPPING.md).

## Remaining environment checks

- Configure valid XAMPP credentials in ignored `modern/server/.env`, run `db:preflight`, `db:init`, and `db:seed` against `f2b_shop_modern`, then verify populated catalogue endpoints and UI.
- In a separate process, set `NODE_ENV=test` and `DB_NAME=f2b_shop_modern_test`, run the same guarded setup/seed commands, then `npm run test:api`.
- Start the PHP Base Version and compare populated pages and workflows at desktop and mobile sizes.
- Obtain course approval and configure a loopback-only local SMTP receiver before receipt acceptance checks.

No real payment integration was added. No external email destination was contacted. This report records actual runnable results and blockers; the full parity gate is not marked passed.

# Modern Version implementation status

> **Historical source-branch record:** The Stage 0–8 notes below describe the implementation at `feature/modern-react-express-mongo` (`680da84`). They are retained as source-branch history and are not instructions or claims about the final stack on this branch. The migration-stage record below documents the replacement.

Baseline: `68545781decb4ac6bbebe50e1d592df287e74006` (`main` at kickoff). Source branch: `feature/modern-react-express-mongo`.

## MongoDB to XAMPP MySQL migration stages

- Branch: `feature/modern-express-mysql`, created from the latest source-branch commit `680da84`.
- Audit and relational mapping: PASS — see `MYSQL_MIGRATION_AUDIT.md` and `MYSQL_DATA_MAPPING.md`. Completed before persistence changes.
- Baseline build/typecheck/lint/unit: PASS — build, typecheck, and lint passed; 23 unit/smoke tests passed on 2026-10-31.
- Baseline API integration: BLOCKED — all four Mongo integration groups report `ECONNREFUSED 127.0.0.1:27017`; no MongoDB service is running.
- XAMPP MySQL: TCP port `127.0.0.1:3306` is reachable. No database was queried, seeded, initialized, or modified during audit.
- Protected Base Version: no PHP files or root database files changed; `f2b_shop` was not accessed.
- Stage A implementation: PASS for code — `mysql2/promise` pool, database allowlist guards, versioned Modern-only DDL, non-destructive `db:init`, connection/schema preflight, readiness probe, parameterized MySQL session store with expiry pruning, and explicit test-only MemoryStore injection are implemented. `connect-mongo` was removed in Stage A; Mongoose was removed during final cleanup after all runtime consumers migrated.
- Stage A `npm run build`: PASS with `NODE_OPTIONS=--max-old-space-size=768` after one earlier Node heap allocation failure at the default limit.
- Stage A `npm run typecheck`: PASS.
- Stage A `npm run lint`: PASS.
- Stage A `npm run test`: PASS — 27 tests, including session serialization/expiry, SQL parameterization, privilege parsing, and Base database rejection.
- Stage A `npm run test:e2e`: PASS — 6 Chromium browser checks against the existing local app; UI code was not changed.
- Stage A `npm run db:preflight`: BLOCKED — XAMPP TCP listener responds, but local `root` with empty password is rejected (`Access denied ... using password: NO`). No `.env` exists and no database/schema was created.
- Stage A `npm run test:api`: BLOCKED at that checkpoint — the source-branch integration harness still required MongoDB. Stage F replaced it with the guarded MySQL test harness; that harness now reaches its test-DB readiness gate but remains blocked by local MySQL authentication/schema availability.
- Stage A MySQL schema/session integration: BLOCKED until valid local credentials are placed in ignored `server/.env`; do not paste credentials into chat or commit them. `db:init` was not run and no schema was created.
- At the Stage A checkpoint, the remaining B–F stages were pending. They have since been implemented and committed as recorded below.
- Stage A commit/push: PASS — `979ae2a` (`feat(modern): introduce MySQL schema and session foundation`), pushed to `feature/modern-express-mysql`.
- Stage B implementation: PASS — catalogue list/detail/home/meta queries and current cart/checkout product lookups now use parameterized MySQL; fixed sort allowlist, LIKE wildcard escaping, exact decimal price bounds, and non-destructive SQL catalogue upserts preserve the existing endpoints and DTOs.
- Stage B `npm run build`, `npm run typecheck`, `npm run lint`: PASS.
- Stage B `npm run test`: PASS — 28 tests, including SQL catalogue filter behavior and exact decimal conversion.
- Stage B `npm run test:e2e`: PASS — all 6 Chromium checks against the local Modern server.
- Stage B `npm run db:seed`: BLOCKED — local XAMPP rejects `root` with an empty password (`Access denied ... using password: NO`). Seed performed no writes. The catalogue API against persisted SQL rows and the seed replay remain unverified until valid credentials are configured in ignored `modern/server/.env`.
- Stage B checkpoint: code complete; continue with Stage C SQL order persistence. MySQL-backed behavior remains environment-blocked until credentials/schema are available.
- Stage B commit/push: PASS — `a41629c` (`feat(modern): migrate catalogue reads and seed to MySQL`), pushed to `feature/modern-express-mysql`.
- Stage C implementation: PASS — checkout now writes order headers and immutable item snapshots in one InnoDB transaction, stores only the SHA-256 guest access hash, allocates stable numeric order/item IDs, and uses a unique key plus request fingerprint for idempotency and conflicting-key rejection. Cart/confirmation/guest order routes and local receipt reads/status claims use SQL repositories.
- Stage C `npm run build`, `npm run typecheck`, `npm run lint`: PASS.
- Stage C `npm test`: PASS — 29 tests, including checkout validation and stable/conflicting request fingerprint checks.
- Stage C `npm run test:e2e -- --timeout=15000`: PASS — all 6 Chromium checks.
- Stage C MySQL order, rollback, snapshot, guest-access, and concurrent-idempotency integration: BLOCKED — XAMPP rejects the available `root`/empty-password login. No MySQL order was created, and neither development nor test DB was initialized.
- Stage C commit/push: PASS — `e795366` (`refactor(modern): port checkout and order access to MySQL`), pushed to `feature/modern-express-mysql`.
- Stage D implementation: PASS — staff order pagination/status changes, mock-up publish/revision, customer review history, approval aggregation, and mock-up authorization now use parameterized SQL and guarded InnoDB transactions/row locks. Numeric SQL IDs preserve the REST string ID shape. Guest session rotation now preserves cart, prior grants, and checkout confirmation state. Mongo is no longer needed to start the Modern runtime; health is based on MySQL schema readiness only.
- Stage D schema: PASS — migration `003_mockup_order_item_ownership.sql` adds a composite ownership FK so a mock-up cannot refer to an item from another order.
- Stage D `npm run build`, `npm run typecheck`, `npm run lint`: PASS.
- Stage D `npm test`: PASS — 29 tests, including existing status-transition, revision, partial approval, bcrypt, and local asset validation tests.
- Stage D `npm run test:e2e -- --timeout=15000`: PASS — all 6 Chromium checks. An initial invocation used an incorrect local Chromium path and failed to launch; rerun with `chromium-1248\\chrome-win64\\chrome.exe` passed.
- Stage D MySQL staff/customer lifecycle and ownership/locking integration: BLOCKED — no valid XAMPP credentials are available; no SQL fixtures or orders were created.
- Stage D commit/push: PASS — `227d2b7` (`refactor(modern): port staff mockup workflow to MySQL`), pushed to `feature/modern-express-mysql`.
- Stage E implementation: PASS — receipts are composed only from committed SQL order/item snapshots; recipient and transport remain exact-allowlisted and loopback-only, disabled by default, and SMTP runs after the order transaction. Atomic SQL claims prevent concurrent sends; a 10-minute claim lease recovers a worker that exited while queued. `sent` means the allowlisted SMTP server accepted the message, not that an inbox delivered it. No real payment or external mail integration was added.
- Stage E schema: PASS — migration `004_receipt_claim_lease.sql` adds the SQL receipt claim lease timestamp.
- Stage E `npm run build`, `npm run typecheck`, `npm run lint`: PASS.
- Stage E `npm test`: PASS — 29 tests, including local recipient/transport fail-closed checks and receipt snapshot formatting without customer email.
- Stage E SQL receipt claim and local SMTP acceptance: BLOCKED — MySQL authentication is unavailable and no local SMTP receiver is configured; no message was sent.
- Stage E commit/push: PASS — `a57e166` (`refactor(modern): preserve local receipts in MySQL`), pushed to `feature/modern-express-mysql`.

## Stage F — cleanup, SQL integration harness, and final documentation

- Implementation: PASS — all runtime persistence now uses loopback XAMPP MySQL through `mysql2/promise`; removed all Mongoose models, dependency, Mongo URI, startup connection, and legacy test setup. Kept React 19/Tailwind 3/Express 5, REST routes/DTOs, local-only disabled-by-default receipt, and no payment gateway.
- Test harness: PASS for code — rewrote API integration setup to require exactly `NODE_ENV=test`, `DB_NAME=f2b_shop_modern_test`, a ready migrated schema and the seeded catalogue. It creates unique checkout keys and scopes product/order/session cleanup to its own fixtures; it never drops databases/tables.
- Documentation: PASS — refreshed the Windows XAMPP runbook, data mapping, API contract, parity report, root README Modern entry, and migration results. Mongo names remain only in clearly labeled source-history/audit context.
- `npm run test:api`: BLOCKED — test setup requires the isolated MySQL schema and seed; preflight with the available root/empty password is rejected by XAMPP (`Access denied ... using password: NO`). The four API integration groups did not run, and no DB data changed.
- Final `npm run build`, `npm run typecheck`, `npm run lint`: PASS.
- Final `npm test`: PASS — 30 tests, 0 failures.
- Final `npm run test:e2e -- --timeout=15000`: PASS — 6 Chromium browser checks.
- Dependency/source audit: PASS — no active Mongo references or Mongo packages in `modern/server`, package manifests/lockfile, or `.env.example`; references retained in source-branch history/audit only.
- `npm run db:init`, `npm run db:seed`: BLOCKED for the same XAMPP authentication error; no Modern schema or catalogue was created.
- Base PHP source: PASS — no PHP or root database files were modified. Development/test DB separation is explicit; `f2b_shop` remains protected.
- Final migration completion: code and cleanup complete; persisted SQL parity, populated customer/staff journeys, PHP visual comparison, and local SMTP acceptance remain blocked by unavailable credentials/runtime/receiver as listed in `MYSQL_MIGRATION_RESULTS.md` and `PARITY_RESULTS.md`.
- Stage F commit/push: PASS — `5877236` (`test(modern): remove Mongo and finalize MySQL migration`), pushed to `feature/modern-express-mysql`.
- Stage F DTO parity fix and final verification commit/push: PASS — `96dd3b2` (`fix(modern): preserve order and review DTO identifiers`); final build/typecheck/lint/unit rerun passed afterward.

## Stage 0 — baseline and parity contract

- **Source review:** PASS (PHP pages, includes, CSS/JS, MySQL schema/seed/migration, local mail docs and Week 7 reports inspected).
- **Parity and data contracts:** PASS (see `BASELINE_PARITY.md`, `DATA_MAPPING.md`, `API_CONTRACT.md`).
- **Legacy browser screenshots:** BLOCKED; no running PHP server/browser session available during analysis.
- **Automated Playwright suite in baseline:** absent; Week 7 test files are reports/matrices.
- **Runtime legacy tests:** not run; PHP and local MySQL runtime commands unavailable in this environment.
- Commit/push: PASS — `0b78514f0f61b6a0cd86ae8e7ffa9f02fc7b8527`.

## Stage 1 — runnable foundation

- Implementation: PASS — React 19, React Router, Tailwind CSS 3, Vite/TypeScript, Express 5, Mongoose, Mongo-backed sessions, readiness/CSRF routes, error handling, Windows launch script and local legacy assets are scaffolded under `modern/`.
- `npm run build`: PASS (server TypeScript + Vite production build).
- `npm run typecheck`: PASS (server and client).
- `npm run lint`: PASS (server and client ESLint).
- `npm run test`: PASS — 4 API smoke tests (health response, CSRF session token, JSON 404, request body size limit).
- `npm run test:e2e`: PASS — 2 Playwright checks (home/hero and nested route refresh) against a running local app. This environment used existing Chromium revision 1248 via `PLAYWRIGHT_CHROMIUM_EXECUTABLE`; Playwright's pinned 1181 download was attempted but stopped with `ENOSPC`.
- Live frontend/API: PASS — `http://127.0.0.1:5173` served the React page (HTTP 200); `http://127.0.0.1:4000/api/v1/health` returned sanitized 503 with `database: not_configured` when Mongo was absent.
- Mongo replica-set connection and `npm run db:preflight`: BLOCKED — connection refused at `127.0.0.1:27017`; no local MongoDB server is running. The executable installation state is unknown. Mongoose transactional stages and persistent session behavior remain unverified.
- Legacy PHP/MySQL/browser checks: not run; PHP and MongoDB executables are unavailable on PATH.
- Commit/push: PASS — `75ed108e6b02ea04e90d22d8e3ebad1b00e57409` (`chore(modern): scaffold React 19 Express 5 and Mongo stack`).

## Stage 2 — verified catalogue and public API

- SQL source parsing: PASS — `node-sql-parser` 5.4.0 parses the complete MySQL seed and extracts literal category/product inserts.
- Source snapshot: PASS — 6 categories, 64 products, 17 colours, 16 new, 16 top-selling; 256 image URLs retained, 256 unique, 0 duplicates; prices $24.90–$109.90.
- Mongoose catalogue models/indexes and guarded idempotent seed script: implemented; seed is restricted to loopback MongoDB database `f2b_shop_modern`.
- Public endpoints implemented: `/api/v1/catalogue/meta`, `/api/v1/products`, `/api/v1/products/:id` (legacy ID or slug), `/api/v1/home`.
- `npm run build`: PASS (Express TypeScript + Vite production build).
- `npm run typecheck`: PASS (server and client).
- `npm run lint`: PASS (server and client).
- `npm run test`: PASS — 8 tests (4 foundation API checks, 4 seed/filter/sort unit checks).
- `npm run test:api`: BLOCKED — integration suite targets only loopback `f2b_shop_modern_test`; connection refused at `127.0.0.1:27017`. The suite reports the dependency blocker and does not clean or alter any non-test DB.
- `npm run db:seed`: BLOCKED — SQL parsing/validation completes, then Mongo connection is refused at `127.0.0.1:27017`; no database writes occurred.
- Catalogue API behavior against persisted Mongo data, idempotent seed replay, and exact returned sort/filter combinations remain unverified until the replica set is available.
- Commit/push: PASS — `32307fc8e1147a024dd6045490a0d267555d6465` (`feat(modern-api): migrate catalogue and expose product endpoints`). Mongo verification remains blocked as recorded above.

## Stage 3 — home, catalogue and product experience

- Implementation: PASS — React routes render the editorial home, query-backed shop grid/filter sidebar, and product detail/gallery. All catalogue content and stats come from the Stage 2 APIs; product cards retain the original gallery URLs and show a neutral unavailable state if an image fails.
- Parity: PASS for reviewed copy, hero/category assets, source palette, repeated `category`/`colour` query keys, `search`/`q`, `min_price`/`max_price`, sort keys, product IDs and legacy size choices. Shop state stays in the URL for refresh and browser navigation.
- Accessibility and responsive behavior: semantic headings/landmarks, labeled filters and quantity, button states for gallery/size, responsive grids, and reduced-motion global CSS are present.
- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS (no warnings).
- `npm run test`: PASS — 8 server/foundation/parser/filter tests.
- `npm run test:e2e`: PASS — 2 browser checks (home brand/hero and shop deep-link refresh) using the existing Chromium 1248 executable. The local test server ran with Mongo unset; pages were checked through their loading state, not seeded product rendering.
- Mongo-backed page data and visual comparison with the PHP site: BLOCKED — no local MongoDB service/seed and no legacy PHP browser session; see Stage 0 and Stage 2 notes.
- Commit/push: PASS — storefront implementation `1214427` (`feat(modern-storefront): build home catalogue and product pages`), pushed to `feature/modern-react-express-mongo`.

## Later stages

## Stage 4 — cart, checkout and confirmation

- Implementation: PASS — session cart CRUD, CSRF guard, current catalogue/colour/size/quantity revalidation, server-side integer-cent totals, checkout validator/messages, idempotent transactional order/snapshots, cart clear after commit, order state `ORDER_PLACED`, session-scoped confirmation and one-time raw guest code are implemented. React product add-to-bag, cart, checkout, and confirmation pages are wired to these endpoints.
- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS (no warnings).
- `npm run test`: PASS — 12 server/parser/cart/checkout tests, including quantity 1/2/99, totals/half-cent behavior, exact checkout messages and control-character rejection.
- `npm run test:e2e`: PASS — 3 browser checks (home/hero, filtered shop refresh, empty cart and continue-shopping link). Test server ran without Mongo; no order or product fixture was available in browser.
- `npm run test:api` against loopback `f2b_shop_modern_test`: BLOCKED — `ECONNREFUSED 127.0.0.1:27017`. The guarded suite now includes CSRF, cart quantities/limits, checkout validation, snapshot after product price edit, order idempotency, cart clearing and one-time guest code; none of its Mongo assertions ran.
- Mongo replica-set transaction behavior, order persistence, and failed-transaction cart retention: BLOCKED until the local MongoDB replica set is started.
- Commit/push: PASS — `c5432e8` (`feat(modern): port cart checkout and guest confirmation`), pushed to `feature/modern-react-express-mongo`. Mongo persistence gate remains BLOCKED as recorded above.

## Stage 5 — guest order access and permissions

- Implementation: PASS — My Orders lookup accepts credentials in a POST body, stores only SHA-256 in Mongo, compares hashes with `timingSafeEqual`, returns a generic failure message, limits failed lookups, regenerates session ID after success, and exposes order details only to session-authorized order numbers. React lookup keeps both credentials out of the URL.
- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS (no warnings).
- `npm run test`: PASS — 13 unit/foundation/parser/cart/checkout/access tests.
- `npm run test:e2e`: PASS — 4 browser checks (home/hero, filtered shop refresh, empty cart and guest lookup form) against the local app using existing Chromium 1248. The app server was launched directly from the already-built output after `npm run dev` hit a transient Node heap failure; browser checks themselves passed.
- `npm run test:api`: BLOCKED — loopback `f2b_shop_modern_test` cannot connect (`ECONNREFUSED 127.0.0.1:27017`); the expanded session-isolation, wrong-code, generic-error and session-rotation assertions have not run.
- Commit/push: PASS — `1c86ff6` (`feat(modern): implement guest order access and authorization`), pushed to `feature/modern-react-express-mongo`. Mongo privacy integration remains BLOCKED as recorded above.

Stage 7 implementation is complete. Stage 8 checks are recorded; its environment-dependent parity gates remain blocked. Each section includes evidence, blockers and commit SHA.

## Stage 7 — course-compliant local-only receipt

- Implementation: PASS — default-disabled local receipt, exact local recipient allowlist, literal loopback SMTP restriction, plain-text rendering from committed snapshots, atomic send claim/status timestamps, and post-commit non-gating handoff are implemented. Customer email is never used as the recipient.
- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS (no warnings).
- `npm run test`: PASS — 23 tests, including recipient/transport fail-closed checks, receipt text/totals/snapshot fields, and absence of customer email.
- SMTP receiver / receipt inbox gate: BLOCKED — no local SMTP listener is available in this environment; no mail was sent. Mongo order persistence is also required to test the handoff status stamp.
- `npm run test:e2e`: PASS — 5 browser checks (home, nested route refresh, empty cart, guest lookup URL privacy, staff-not-configured state); app ran without Mongo or SMTP.
- `npm run test:api`: BLOCKED — Mongo test DB remains unavailable; integration coverage asserts disabled email state and will cover it once Mongo is running. No external SMTP was contacted.
- Commit/push: PASS — `6a3f096` (`feat(modern): add allowlisted local order receipt`), pushed to `feature/modern-react-express-mongo`.

## Stage 6 — staff, mock-ups and full lifecycle

- Implementation: PASS — PHP-compatible bcrypt login/logout with session rotation, staff-only paginated APIs, local image allowlist, transactional publish/revision/review flow, customer review UI/history, and atomic guarded order transitions are implemented.
- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS (no warnings).
- `npm run test`: PASS — 21 tests, including bcrypt `$2y$`, staff guest rejection, image allowlist/traversal, revision rules, valid lifecycle edges and partial approval.
- `npm run test:e2e`: PASS — 5 browser checks, including the staff-not-configured message; app server launched directly from built output. No staff password or Mongo database was configured for this browser run.
- `npm run test:api` against loopback `f2b_shop_modern_test`: BLOCKED — `ECONNREFUSED 127.0.0.1:27017`. The four guarded suites include catalogue, cart/checkout, guest session isolation, staff login, first publish, change request, stale revision rejection, revision publish, customer approval, staff processing/completion, and invalid transition. None of these persistent assertions ran.
- Full staff–customer–staff lifecycle, transaction behavior, and order/mock-up persistence: BLOCKED until the local MongoDB replica set is available.
- Commit/push: PASS — `46f31b7` (`feat(modern): port staff mockup and review workflow`), pushed to `feature/modern-react-express-mongo`. Mongo lifecycle gate remains BLOCKED as recorded above.

## Stage 8 — parity QA, Playwright and final documentation

- `npm run build`: PASS.
- `npm run typecheck`: PASS.
- `npm run lint`: PASS.
- `npm run test`: PASS — 23 tests, 0 failures.
- Playwright: PASS — 6 browser tests, including keyboard activation of skip-to-main. Modern home captures at 1440×900 and 390×844 were reviewed in `docs/parity-screenshots/`; API-backed shelves remain in loading state without MongoDB.
- `npm run test:api`: BLOCKED — all four integration groups fail at `ECONNREFUSED 127.0.0.1:27017`; no MongoDB replica set is available.
- Base/Modern locations are documented in the root README. `docs/PARITY_RESULTS.md` records evidence, screenshots, remaining gaps and exact blockers.
- PHP/MySQL differential testing and populated customer/staff journey screenshots: BLOCKED because the legacy runtime and MongoDB test service are unavailable. Existing PHP files and PHP E2E tests remain unchanged.
- Local SMTP receipt acceptance remains BLOCKED; no receiver was running and no mail was sent. No real payment integration was added.
- Stage 8 parity gate: BLOCKED pending MongoDB-backed journeys, local legacy comparison, and approved loopback SMTP verification.
- Commit/push: PASS — `e1a34b8` (`test(modern): complete parity E2E and documentation`), pushed to `feature/modern-react-express-mongo`.

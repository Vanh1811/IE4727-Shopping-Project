# Modern Version implementation status

Baseline: `68545781decb4ac6bbebe50e1d592df287e74006` (`main` at kickoff). Branch: `feature/modern-react-express-mongo`.

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

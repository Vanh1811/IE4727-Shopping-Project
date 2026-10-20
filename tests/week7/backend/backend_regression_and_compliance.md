# Week 7 Backend Regression and Base Version Compliance

## Environment and setup

Branch: `feature/week7-backend-qa` from integrated `main` at `3110b2a`.

Logical commits completed before this report:

- `9656f99` — `test: audit backend authorization and security`
- `b1326d1` — `fix: preserve cart when database unavailable`
- `e934f67` — `test: verify mockup lifecycle and local acknowledgement`

PHP CLI, MySQL/MariaDB and browser automation were unavailable. Node.js was
available and checked both project JavaScript files. No project database, local
mail server or external recipient was used.

## Regression matrix

| Test ID | Input / prerequisite | Expected output | Test method | Actual result | Status | Evidence |
|---|---|---|---|---|---|---|
| B4-01 | Run `php -l` on changed PHP and project PHP files | No PHP syntax errors | PHP CLI | PHP CLI is unavailable | BLOCKED | PHP lint could not run |
| B4-02 | Run Node syntax checks on `js/main.js` and `js/mockup.js` | No JavaScript syntax errors | `node --check` | Both files passed | PASS | Local Node.js check |
| B4-03 | Check working-tree and staged whitespace | No diff-check errors | `git diff --check` | No errors before this report commit | PASS | Git diff check |
| B4-04 | Scan PHP/JS/source inventory for Base Version structure and prohibited dependencies | Required pages, forms, SQL operations, CSS and no iframe/AJAX/jQuery/API dependency | Static source scan | Source evidence found for all required structures; prohibited dependency patterns were absent | PASS | Page/source inventory and static scan |
| B4-05 | Homepage -> shop -> product -> cart -> checkout -> confirmation | Valid order saves once and renders authorized confirmation | Browser + PHP/MySQL | Runtime environment unavailable | BLOCKED | Customer flow not executed |
| B4-06 | Invalid/cross-customer confirmation, guest unlock and mock-up IDs | No protected PII or review data is exposed | Private browser + DB | Runtime environment unavailable | BLOCKED | `security_audit.md` B1-01 to B1-05 |
| B4-07 | Forged/missing CSRF and direct GET state mutations | State-changing requests fail; GET does not mutate state | Browser/HTTP + PHP | Runtime environment unavailable | BLOCKED | `security_audit.md` B1-06, B1-08, B1-13 |
| B4-08 | Fresh schema, Week 5 migration, successful/failed order transaction | Keys, snapshots, totals and rollback are correct | MySQL CLI + disposable DB | MySQL/MariaDB unavailable | BLOCKED | `database_transaction_results.md` B2-01 to B2-09 |
| B4-09 | Mock-up publish, change request, revision, multi-item approval and status progression | Valid state machine persists; stale/forged transitions fail | Browser + MySQL | Runtime environment unavailable | BLOCKED | `mockup_email_workflow_results.md` B3-01 to B3-08 |
| B4-10 | Local acknowledgement with permitted local recipient | Delivery proof and `email_sent_at` reflect actual success; refresh does not resend | Local mail server + DB | Mail flags/server unavailable; no attempt made | BLOCKED | `mockup_email_workflow_results.md` B3-09 to B3-10 |

## Outcome counts

| Outcome | Count |
|---|---:|
| PASS | 3 |
| FAIL | 0 |
| BLOCKED | 7 |
| NOT_RUN | 0 |

## Defects, blockers and ownership

### Critical defects

None confirmed by static source review. Runtime authorization and privacy
verification remain blocked, so this is not a security acceptance claim.

### Major defects

None confirmed. Duplicate checkout POST behavior has no visible server-side
idempotency key and needs a disposable browser/DB race test before final
acceptance.

### Minor findings

- No staff-login or guest-code throttling was found. Basic scalar/length checks
  exist, but repeated-attempt mitigation is unverified.
- PHP lint could not be run because PHP CLI is unavailable.

### Blockers

- PHP CLI unavailable: all `php -l` checks are blocked.
- MySQL/MariaDB unavailable: schema, migration, transaction, rollback and
  workflow persistence tests are blocked.
- Browser automation unavailable: privacy, CSRF, form and end-to-end tests are
  blocked.
- `F2B_LOCAL_MAIL_ENABLED`, `F2B_LOCAL_MAIL_TO` and
  `F2B_STAFF_PASSWORD_HASH` were not set; no local email delivery test was
  attempted.

## Base Version compliance mapping

| Requirement | Source evidence | Classification |
|---|---|---|
| One homepage plus 4–10 additional content pages | `index.php`, `shop.php`, `product.php`, `cart.php`, `checkout.php`, `confirmation.php`, `orders.php`, `mockup.php`, `admin-login.php`, `admin-orders.php` | MET by static inventory; runtime unverified |
| Content pages have titles, text and images | Page titles plus rendered catalogue/order/mock-up content and local image paths | MET by source; runtime unverified |
| Meaningful table | Authorized customer orders and staff order management tables in `orders.php` and `admin-orders.php` | MET by source; runtime unverified |
| Four-or-more-field form processed by PHP | Checkout form and `f2bOrderValidateCheckout()` / `f2bOrderCreate()` | MET by source; runtime unverified |
| SQL `SELECT`, `INSERT` and `UPDATE` | Prepared statements in catalogue, checkout, review and staff workflow helpers | MET by source; runtime unverified |
| Dynamically generated PHP page | `shop.php`, `product.php`, `orders.php`, `admin-orders.php` | MET by source; runtime unverified |
| HTML5/JavaScript/PHP validation | Native constraints, vanilla JS custom validity and PHP validators | MET by source; browser/runtime unverified |
| External CSS with styles | Page stylesheets under `css/` | MET by source; visual runtime unverified |
| No external form handler, frames/iframe, AJAX, jQuery or JSON API dependency | Static scan found no prohibited patterns; forms post to local PHP endpoints | MET by static scan; runtime unverified |
| Required local acknowledgement email | `includes/mail.php` supports an explicitly configured local recipient | UNVERIFIED/BLOCKED; no permitted local server configuration or delivery proof |
| Full Base Version functional acceptance | Requires real PHP/MySQL/browser execution and failure-path evidence | UNVERIFIED/BLOCKED |

## Week 8 handoff

Before final coursework acceptance, run the blocked matrices with disposable
local data, capture sanitized screenshots and browser console results, execute
fresh-schema and migration checks, exercise rollback and duplicate-submit
behavior, and perform one permitted local-mail delivery test. Record database
status/review history and `email_sent_at` evidence separately from onscreen
confirmation.

No Member A customer-form change is currently required from the backend audit.
Do not add external services, payment processing or new content pages while
these Base Version checks remain unverified.

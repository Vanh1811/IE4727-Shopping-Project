# F2B SHOP Playwright E2E

Development-only Playwright tests for the PHP/MySQL site served by XAMPP. They do not change the production PHP application. Browser tests use Chromium and run serially. Read-only checks are available immediately; tests that create orders or workflow records require explicit local safety gates.

## Current run status

The harness is implemented and typechecked. The configured default URL, `http://127.0.0.1/f2b-shop/`, returned HTTP 404 in the implementation environment, so browser assertions have not been run. See [run results](reports/run-results.md), [scenario matrix](reports/test-matrix.md), and [issues](reports/issues.md). Do not interpret discovered tests as executed tests.

## Requirements and install

Use Node.js 20 or newer, npm, XAMPP Apache/MySQL, and this repository placed under the Apache document root. From the repository root:

```powershell
npm install
npx playwright install chromium
Copy-Item .env.e2e.example .env.e2e
```

On macOS/Linux, use `cp .env.e2e.example .env.e2e`. Edit the ignored `.env.e2e` and set `E2E_BASE_URL` to the exact local URL for this checkout. Defaults only accept loopback HTTP URLs. Do not point the runner at a public site. Private-LAN hosts require the explicit `E2E_ALLOW_PRIVATE_HOST=1` opt-in, and mutation tests still require the gates below.

## Safe read-only checks

Start Apache and MySQL in XAMPP, then run:

```powershell
npm run e2e:preflight
npm run e2e:list
npm run e2e:smoke
npm run e2e -- --project=chromium
npm run e2e:ui
npm run e2e:report
```

On macOS/Linux the npm commands are identical. To run a file or tag, use `npm run e2e -- e2e/specs/catalogue.spec.ts`, `npm run e2e -- --grep @smoke`, or `npm run e2e -- --grep @security`. The UI and HTML report may contain synthetic data; keep `playwright-report/` and `test-results/` local.

The smoke and catalogue checks do not submit forms that create orders. Cart tests use the isolated browser session. Tests use visible page content and assert useful outcomes, not just successful HTTP responses. Browser diagnostics report JavaScript page errors and failed first-party app requests; unrelated third-party failures are excluded. Product image failures are reported separately because CDN/network restrictions can block them without indicating the PHP app is unhealthy.

## Dedicated database setup before any mutation

Never run persistent tests against the normal `f2b_shop` database. Provision a disposable local database named exactly `f2b_shop_e2e` and use synthetic records only.

1. Back up the development database using your normal XAMPP/phpMyAdmin process.
2. Create `f2b_shop_e2e` in local MySQL/MariaDB. Import `database/schema.sql` and `database/f2b_product_seed.sql` into that database. The current schema contains the workflow tables; inspect it before importing migrations. Do not run `002_week6_workflow.sql` on a schema that already includes those tables.
3. Configure the Apache/PHP process to connect to `f2b_shop_e2e` with `F2B_DB_NAME=f2b_shop_e2e` (and the appropriate `F2B_DB_HOST`, `F2B_DB_USER`, and `F2B_DB_PASSWORD`), then restart Apache. These are Apache process settings; Node's `.env.e2e` does not set them.
4. Verify PHP itself uses the test database. Compare a seeded product ID/name in MySQL with the rendered `product.php?id=<id>` page. Where this does not prove DB identity, use a temporary local-only diagnostic outside version control or manually verify the PHP connection configuration. Record that verification by setting `E2E_PHP_DB_VERIFIED=1` in `.env.e2e` only after checking it. The runner cannot infer Apache's DB target from Node settings.
5. Confirm the database contains no personal/customer data and accept that synthetic test orders and reviews accumulate. The tests do not clean up database rows.

For optional UI-only DB assertions, set `E2E_DB_NAME=f2b_shop_e2e`, `E2E_DB_HOST=127.0.0.1`, `E2E_DB_PORT=3306`, and a local read-only `E2E_DB_USER`/`E2E_DB_PASSWORD`. The helper only issues parameterized `SELECT` queries and checks the selected database name. Keep passwords only in ignored `.env.e2e`.

### Enable persistent order and staff tests

Only after verifying Apache is connected to the disposable DB, set these in `.env.e2e`:

```dotenv
E2E_ALLOW_MUTATIONS=1
E2E_DB_NAME=f2b_shop_e2e
E2E_PHP_DB_VERIFIED=1
```

Checkout/order tests then become eligible. Staff tests additionally need `E2E_ENABLE_STAFF=1` and `E2E_STAFF_PASSWORD` in `.env.e2e`; separately configure the Apache environment with the corresponding `F2B_STAFF_PASSWORD_HASH`. Never commit either secret. Disable trace and screenshots for tests that reveal the one-time guest access code. Test logs and annotations must not include that code.

Mutation tests place synthetic orders and never drop, truncate, or bulk-delete records. There is no automated database reset. Review each order/workflow run and manually reset only through your own controlled local DB process if needed.

## Optional local receipt testing

Mail tests are opt-in and remain blocked until the receipt feature, PHP configuration, and local Mailpit are present. Mailpit must capture local SMTP only, with no internet relay. Configure XAMPP PHP `mail()` to use the local Mailpit SMTP listener (commonly `127.0.0.1:1025`), enable `F2B_LOCAL_MAIL_ENABLED=1`, and set `F2B_LOCAL_MAIL_TO` to your permitted local test mailbox. This configuration belongs to Apache/PHP and is independent from Node settings.

Then set `E2E_ENABLE_MAIL=1`, `E2E_PHP_MAIL_VERIFIED=1`, `E2E_MAILPIT_URL=http://127.0.0.1:8025`, and `E2E_EXPECTED_LOCAL_RECIPIENT` to the same local test mailbox. The helper uses read-only Mailpit API calls and filters messages for the test order. It never clears the inbox or sends mail itself. Do not use Gmail, Outlook, or any external recipient.

## Troubleshooting

See [SUPPORT.md](SUPPORT.md) for diagnostics, common blockers, and safe environment checks. See [test matrix](reports/test-matrix.md) for test IDs and current status. See [run results](reports/run-results.md) for actual commands and output summary.

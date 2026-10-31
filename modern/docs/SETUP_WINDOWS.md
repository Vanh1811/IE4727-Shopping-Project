# Windows setup: Modern Version

The PHP/MySQL Base Version stays in the repository root and uses its existing XAMPP database, `f2b_shop`. The Modern Version uses React 19, Tailwind CSS 3, Vite/TypeScript, Express 5 and XAMPP MySQL/MariaDB through `mysql2/promise`. No MongoDB service is used.

## Requirements

- Node.js 22.12 or newer and npm.
- XAMPP MySQL/MariaDB running at `127.0.0.1:3306` with InnoDB enabled.
- A local MySQL account with privileges to create and alter `f2b_shop_modern` and `f2b_shop_modern_test`. The example blank root password applies only to an untouched local XAMPP install; use your configured local credentials.
- Chromium for Playwright tests. An existing compatible installation can be supplied with `PLAYWRIGHT_CHROMIUM_EXECUTABLE`.

## Development database

From PowerShell:

```powershell
Set-Location modern
npm ci
Copy-Item server/.env.example server/.env
```

Edit the ignored `server/.env`: set `DB_USER`/`DB_PASSWORD` for XAMPP, keep `DB_HOST=127.0.0.1` and `DB_NAME=f2b_shop_modern`, and set `SESSION_SECRET` to at least 32 random characters. `STAFF_PASSWORD_HASH` accepts a locally generated bcrypt hash (including PHP `$2y$` hashes); staff access stays disabled until configured. Keep local credentials and hashes out of source control.

Initialize only the Modern development schema and load the catalogue:

```powershell
npm run db:preflight
npm run db:init
npm run db:preflight
npm run db:seed
npm run dev
```

`db:init` creates the allowlisted Modern schema if needed and applies additive migrations. It does not drop tables or seed data. `db:seed` upserts only the six categories and 64 source products and never clears orders or sessions. Both scripts reject the PHP `f2b_shop` database and non-loopback hosts. Never run the root `database/schema.sql` against the Modern connection.

Open `http://127.0.0.1:5173`; Express serves `/api/v1` at `http://127.0.0.1:4000`. Vite proxies `/api` so browser requests share the session origin. Use `127.0.0.1` consistently when testing cookies. Health returns HTTP 200 only when MySQL and the latest Modern migrations are ready; otherwise it returns a sanitized 503.

## Isolated API test database

The API integration suite writes only to `f2b_shop_modern_test`; it never uses the development DB or PHP Base Version. First copy local credentials from `server/.env`, then run these commands in a fresh PowerShell window so the process overrides the development database name:

```powershell
$env:NODE_ENV = 'test'
$env:DB_NAME = 'f2b_shop_modern_test'
npm run db:preflight
npm run db:init
npm run db:seed
npm run test:api
Remove-Item Env:NODE_ENV, Env:DB_NAME
```

The integration harness refuses any other database or host and requires the migrated schema and 64-product catalogue. It uses random idempotency keys, restores the one source product price it changes, and deletes only the order it creates. It does not drop the test database. If XAMPP authentication or schema setup is unavailable, `test:api` is blocked and must not be reported as passed.

## Optional local receipt

`LOCAL_MAIL_ENABLED=0` by default. Keep it disabled unless the course-approved local inbox and receiver are configured. Enabling it requires an exact matching `LOCAL_MAIL_TO` and `LOCAL_MAIL_ALLOWED_TO` using a `.test`, `.localhost`, `.local`, or `localhost` address, and `LOCAL_SMTP_HOST=127.0.0.1` or `::1`. Customer email is never used as the recipient. A `.test` address alone does not block outbound relay; configure the receiver with relay disabled. Review [the local email guide](../../docs/local_email/README.md) and obtain lecturer approval before using a capture inbox. No external mail provider or payment gateway is integrated.

## Verification

```powershell
npm run build
npm run typecheck
npm run lint
npm test
```

Keep `npm run dev` running in one PowerShell window before browser tests in another:

```powershell
npm run test:e2e
```

If Playwright cannot find its pinned Chromium, install it with `npx playwright install chromium` when disk space and network access permit, or set `PLAYWRIGHT_CHROMIUM_EXECUTABLE` to an installed compatible executable.

The Modern Version has no reset command. Never point it at the Base Version database or write to `f2b_shop`.

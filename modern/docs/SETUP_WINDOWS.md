# Windows setup (Modern Version)

The PHP/MySQL Base Version stays in the repository root and continues to use XAMPP. This branch is migrating the Modern Version in stages. Stage A has added XAMPP MySQL infrastructure and persistent MySQL sessions, while catalogue and business repositories still use MongoDB until their later stages. This is a transitional setup, not the final Mongo-free runbook.

## Requirements

- Node.js 22.12 or newer and npm.
- XAMPP MySQL/MariaDB running on `127.0.0.1:3306`; configure a local MySQL user with privileges to create/alter only the `f2b_shop_modern` database. The `.env.example` empty password is only an untouched-XAMPP example.
- MongoDB bound to loopback at `127.0.0.1:27017`, configured as a single-node replica set named `rs0`. Multi-document order and review writes require replica-set transactions.
- Chromium for Playwright browser tests (install with `npx playwright install chromium` when network access is available).

## First run

From PowerShell:

```powershell
Set-Location modern
npm ci
Copy-Item server/.env.example server/.env
```

Edit `server/.env`: enter the local XAMPP credentials in `DB_USER`/`DB_PASSWORD`, keep `DB_HOST=127.0.0.1`, and leave `DB_NAME=f2b_shop_modern`. Generate a local session secret (at least 32 characters). Until the remaining persistence stages are complete, also set `MONGODB_URI` to the local replica-set URI shown in the example. Replace `STAFF_PASSWORD_HASH` with a bcrypt hash for a locally chosen staff password. The app accepts PHP-compatible `$2y$` hashes. Keep the hash, database password, and plaintext staff password out of source control. Staff login stays disabled while the example placeholder remains. The example mail recipient is a placeholder; local mail stays disabled.

If MongoDB is installed as a Windows service, configure its own local `mongod.cfg` with `bindIp: 127.0.0.1` and `replication.replSetName: rs0`, restart that service, and initialize once from `mongosh`:

```javascript
rs.initiate({ _id: "rs0", members: [{ _id: 0, host: "127.0.0.1:27017" }] })
```

Only initialize a replica set you have configured for this local project. First check and initialize only the Modern SQL database:

```powershell
npm run db:preflight
```

This first preflight checks loopback connectivity, InnoDB, target privileges and reports when the schema is not initialized. `db:init` creates only the allowlisted Modern schema and applies Modern-only migrations; it does not drop or seed anything. Then run:

```powershell
npm run db:init
npm run db:preflight
```

It refuses the `f2b_shop` Base Version database. `db:seed` now performs non-destructive catalogue-only upserts into the configured Modern database and never clears orders or sessions. MongoDB is still temporarily used by order and staff repositories until their later migration stages. Initialize it only for those remaining repository paths, then:

```powershell
npm run db:seed
npm run dev
```

Open `http://127.0.0.1:5173`; the API is at `http://127.0.0.1:4000/api/v1`. Vite proxies `/api` to Express so browser requests share the frontend origin and session cookie. Use `127.0.0.1` consistently; do not mix it with `localhost` when testing cookies. The health endpoint returns 503 unless the Modern MySQL schema and transitional Mongo catalogue are both ready.

## Optional local receipt

`LOCAL_MAIL_ENABLED` defaults to `0`. Keep it disabled unless the course-approved local inbox and a receiver bound to loopback are available. To enable the handoff, set the exact same allowed local recipient in `LOCAL_MAIL_TO` and `LOCAL_MAIL_ALLOWED_TO`, choose a `.test`, `.localhost`, `.local`, or `localhost` address, and keep `LOCAL_SMTP_HOST` at literal `127.0.0.1` or `::1`. Public SMTP hosts and customer checkout addresses are never used as recipients. A `.test` address does not by itself prevent SMTP relaying; configure the receiver to disable outbound relay. Review the repository's [`docs/local_email/README.md`](../../docs/local_email/README.md) and get lecturer approval before using a capture inbox such as Mailpit.

## Verification commands

```powershell
npm run build
npm run typecheck
npm run lint
npm run test
```

Keep `npm run dev` running in one PowerShell window before starting browser tests in a second window:

```powershell
npm run test:e2e
```

If Playwright cannot find its pinned Chromium build, run `npx playwright install chromium` when disk space permits. `PLAYWRIGHT_CHROMIUM_EXECUTABLE` can point to an already installed compatible Chromium executable for local verification.

The MongoDB-backed portions of the Modern Version will be removed as the remaining migration stages complete. Never point the modern server at the PHP MySQL write path. No database reset command is provided.

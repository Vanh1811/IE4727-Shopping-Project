# Windows setup (Modern Version)

The PHP/MySQL Base Version stays in the repository root and continues to use XAMPP. The modern stack runs separately on Node.js and MongoDB.

## Requirements

- Node.js 22.12 or newer and npm.
- MongoDB bound to loopback at `127.0.0.1:27017`, configured as a single-node replica set named `rs0`. Multi-document order and review writes require replica-set transactions.
- Chromium for Playwright browser tests (install with `npx playwright install chromium` when network access is available).

## First run

From PowerShell:

```powershell
Set-Location modern
npm ci
Copy-Item server/.env.example server/.env
```

Edit `server/.env`: generate a local session secret (at least 32 characters), set the Mongo URI to `mongodb://127.0.0.1:27017/f2b_shop_modern?replicaSet=rs0`, and create a staff password hash only when staff login is implemented. The example mail recipient is a placeholder; local mail stays disabled.

If MongoDB is installed as a Windows service, configure its own local `mongod.cfg` with `bindIp: 127.0.0.1` and `replication.replSetName: rs0`, restart that service, and initialize once from `mongosh`:

```javascript
rs.initiate({ _id: "rs0", members: [{ _id: 0, host: "127.0.0.1:27017" }] })
```

Only initialize a replica set you have configured for this local project. Then run:

```powershell
npm run db:preflight
npm run db:seed
npm run dev
```

Open `http://127.0.0.1:5173`; the API is at `http://127.0.0.1:4000/api/v1`. Vite proxies `/api` to Express so browser requests share the frontend origin and session cookie. Use `127.0.0.1` consistently; do not mix it with `localhost` when testing cookies. The Stage 1 health endpoint returns 503 until MongoDB is connected.

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

`db:seed` will be implemented after the SQL catalogue has been parsed and verified in Stage 2. Never point the modern server at the PHP MySQL write path. No database reset command is provided.

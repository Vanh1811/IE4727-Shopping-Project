# E2E support and diagnostics

## First checks

Run these from the repository root after starting XAMPP Apache and MySQL:

```powershell
node --version
npm --version
npm run e2e:preflight
npm run e2e:list
```

Confirm the configured URL opens in the same machine/browser context that runs Playwright. For PowerShell, a quick loopback check is:

```powershell
Invoke-WebRequest -Uri 'http://127.0.0.1/f2b-shop/' -Method Get
```

For macOS/Linux, use `curl -i http://127.0.0.1/f2b-shop/`. A 404 usually means the URL's document-root path does not match the checkout location; use the actual local URL, then rerun preflight. A 500/PHP fatal needs to be fixed in the local XAMPP/app setup before browser tests are meaningful.

## Common blockers

| Symptom | Likely cause | Next step |
|---|---|---|
| Preflight returns app 404 | `E2E_BASE_URL` does not map to this checkout | Correct the local URL and verify `index.php` and `shop.php` render. |
| Product count is zero | DB is not seeded, app is connected to another DB, or URL is wrong | Check the Apache DB configuration and compare a seeded product page. |
| Mutation tests skip | One or more opt-in gates are unset or invalid | Verify the dedicated DB, PHP DB attestation, and mutation flag. Never use `f2b_shop`. |
| Staff scenarios skip | Staff gate/password is missing | Configure the local test password in ignored `.env.e2e` and the corresponding hash in Apache. |
| Mail checks skip | Receipt feature or local Mailpit/PHP mail prerequisites are unavailable | Use the local-only setup in `README.md`; never enable external relay. |
| Chromium launch fails | Browser binary is not installed or blocked by OS policy | Run `npx playwright install chromium` and inspect the reported browser path. |
| Images fail but pages work | External image host/network restriction | Review the separate image probe output; this is not proof of a PHP checkout defect. |

## Reports and sensitive artifacts

`playwright-report/` and `test-results/` are ignored. Traces, screenshots, and HTML reports can contain synthetic customer details or one-time order access codes. Keep them local and inspect before sharing. Sensitive specs disable screenshots/traces. Do not attach raw artifacts containing access codes to issues or commits.

The suite does not reset or clean the E2E database. Synthetic orders and reviews remain for inspection. Do not add a broad delete/reset command to the default test lifecycle.

## Current environment limitation

During implementation, the configured local base URL returned 404 and no seeded product links were detected. Therefore the browser suite was not run, and no application regression has been confirmed. See [run results](reports/run-results.md) and [issues](reports/issues.md).

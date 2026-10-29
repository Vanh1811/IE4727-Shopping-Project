# Application issues observed during E2E preparation

## No application defect confirmed

The configured base URL, `http://127.0.0.1/f2b-shop/`, returned HTTP 404 during preflight. Nearby guessed paths also did not identify the application; the root URL appears to serve the XAMPP dashboard. Since the actual PHP pages were not reachable, no app-level assertion ran and no functional defect is confirmed.

**Reproduction:** Start XAMPP Apache, then run `npm run e2e:preflight` with the default `.env.e2e.example` URL. The base URL and the expected `index.php`/`shop.php` paths return 404.

**Expected:** The local base URL maps to this checkout and renders the F2B Shop homepage/catalogue.

**Actual:** The configured path returns 404 before the application can be inspected.

**Likely cause (hypothesis):** The checkout's Apache document-root folder name differs from `f2b-shop`, or the application is not currently served at that path. This is an environment/path issue until verified otherwise.

**Severity:** Environment blocker; not classified as an application bug.

**Next step:** Set `E2E_BASE_URL` to the URL that serves this checkout, then rerun preflight and browser tests. Do not change production PHP based on this result.

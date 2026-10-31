# Modern Version parity results

Date: 2026-10-31  
Branch: `feature/modern-react-express-mongo`

## Verification summary

| Area | Result | Evidence |
| --- | --- | --- |
| Production build | PASS | `npm run build` compiled Express and generated the Vite production bundle. |
| Type checking | PASS | `npm run typecheck` passed for server and client. |
| Lint | PASS | `npm run lint` passed for server and client. |
| Unit tests | PASS | `npm run test`: 23 passed, 0 failed. Includes source-compatible catalogue parsing/filtering, price rounding, checkout validation, guest access hashing, staff bcrypt, order transitions, mock-up revision rules, and local receipt safeguards. |
| Browser tests | PASS | `node ./node_modules/@playwright/test/cli.js test -c e2e/playwright.config.ts`: 6 passed. Covers home branding, catalogue URL refresh, cart empty state, lookup credential URL privacy, unconfigured staff state, and keyboard skip-link use. Browser ran using the installed Chromium 1248 executable. |
| Desktop/mobile capture | PARTIAL | Modern home screenshots at 1440×900 and 390×844 are in `parity-screenshots/`. They show the Modern Version with the API-backed home shelves still loading because MongoDB is unavailable. |
| Mongo-backed API and staff/customer journeys | BLOCKED | `npm run test:api` compiled successfully, then all four integration groups stopped at `ECONNREFUSED 127.0.0.1:27017`. A local MongoDB replica set and seeded test database are unavailable. |
| Receipt delivery | BLOCKED | No loopback SMTP listener was available on ports 1025 or 25. The receipt remains disabled by default; no email was sent. |
| PHP/MySQL differential and visual comparison | BLOCKED | No local legacy PHP/MySQL runtime or baseline browser session is available. Existing PHP files were left unchanged. |

## Visual and accessibility review

The desktop and mobile captures were reviewed. The home hero, image crop, text hierarchy, category cards, navigation, and responsive two-column category grid render at both viewport sizes. API-backed product counts and product shelves are unresolved in the captures because the Mongo service is not running; these captures are not evidence of data parity with the Base Version.

Desktop (1440×900): [home-desktop.png](parity-screenshots/home-desktop.png)  
Mobile (390×844): [home-mobile.png](parity-screenshots/home-mobile.png)

The browser suite checks the labeled skip-to-main link with keyboard focus and activation. Forms and navigation use semantic labels, headings, landmarks, buttons, and links. A full keyboard and screen-reader review of populated checkout, customer order, mock-up review, and staff pages remains blocked with the unavailable persistent test environment.

## Open verification gaps

- Start a disposable MongoDB replica set, run the guarded SQL seed, and rerun `npm run test:api` plus customer/staff browser journeys.
- Start the PHP/MySQL Base Version, capture matching pages at 1440×900 and 390×844, and compare populated routes and behavior.
- Obtain lecturer approval and start the course-approved loopback SMTP receiver before verifying accepted receipt content and persisted delivery state.

No real payment integration was added. No external email destination was contacted. This report records runnable checks and environment blockers; it does not mark the full parity gate as passed.

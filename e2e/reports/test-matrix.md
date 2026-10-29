# E2E scenario matrix

Status reflects this implementation environment as of 2026-10-29. The app base URL returned 404, so no browser assertion ran. `BLOCKED` means the runtime prerequisite was unavailable; it does not mean the assertion failed.

| ID | Priority | Scenario | Safety | Spec / status |
|---|---|---|---|---|
| E2E-01 | P0 | Homepage and navigation render | Read-only | `smoke.spec.ts` — BLOCKED |
| E2E-02 | P0 | Shop catalogue renders seeded products | Read-only | `smoke.spec.ts`, `catalogue.spec.ts` — BLOCKED |
| E2E-03 | P0 | Search and category/colour filters | Read-only | `catalogue.spec.ts` — BLOCKED |
| E2E-04 | P1 | Sort and price filtering | Read-only | `catalogue.spec.ts` — BLOCKED |
| E2E-05 | P0 | Product details, size, quantity, gallery | Session only | `smoke.spec.ts`, `product-cart.spec.ts` — BLOCKED |
| E2E-06 | P0 | Add, update, remove, clear cart | Session only | `product-cart.spec.ts` — BLOCKED |
| E2E-07 | P0 | Checkout required-field validation | Session only | `checkout.spec.ts` — BLOCKED |
| E2E-08 | P0 | Successful order and item snapshots | E2E DB write gate | `checkout.spec.ts` — BLOCKED |
| E2E-09 | P0 | Guest order access in separate sessions | E2E DB write gate | `guest-access.spec.ts` — BLOCKED |
| E2E-10 | P0 | Unauthenticated staff access denied | Read-only/security | `staff-mockup.spec.ts`, `security.spec.ts` — BLOCKED |
| E2E-11 | P0 | Publish mock-up, request changes, revise, approve | E2E DB + staff gates | `staff-mockup.spec.ts` — BLOCKED |
| E2E-12 | P0 | Partial multi-item approval and status transitions | E2E DB + staff gates | `staff-mockup.spec.ts` — BLOCKED |
| E2E-13 | P0 | Local receipt contains order items and total | E2E DB + mail gates | `mail.spec.ts` — BLOCKED |
| E2E-14 | P1 | Confirmation refresh does not duplicate receipt | E2E DB + mail gates | `mail.spec.ts` — BLOCKED |
| E2E-15 | P1 | Invalid product / forged CSRF rejected | Read-only or security | `smoke.spec.ts`, `security.spec.ts` — BLOCKED |
| E2E-16 | P1 | Unknown/foreign order and mock-up access blocked | E2E DB/security | `guest-access.spec.ts`, `security.spec.ts` — BLOCKED |
| E2E-17 | P1 | Product image load failures surfaced distinctly | Read-only/network | `smoke.spec.ts` — BLOCKED |
| E2E-18 | P1 | JavaScript errors and failed first-party assets recorded | Read-only | `fixtures/browser.ts`, smoke specs — BLOCKED |
| E2E-19 | P1 | Bounded XSS search value is escaped | Read-only/security | `security.spec.ts` — BLOCKED |

The test runner discovers 25 executable Playwright tests across eight specs. Discovery/type checking is separate from execution; see [run results](run-results.md). Optional DB and Mailpit checks are conditional and must be reported independently from UI assertions.

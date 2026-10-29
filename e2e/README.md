# F2B SHOP Playwright E2E

Development-only Playwright tests for the existing PHP/MySQL XAMPP application.
They do not replace or modify the served PHP application. Begin with the
read-only smoke suite; persistent order and staff workflow tests are disabled
unless the dedicated test database and explicit local gates are configured.

## Status

This folder is being implemented in four checkpoints. Current execution results
are in [`reports/run-results.md`](reports/run-results.md); do not treat a test
as passed until it has actually run against the local XAMPP instance.

## Configuration

Copy `.env.e2e.example` to `.env.e2e` and set `E2E_BASE_URL` to the URL for this
checkout. By default the runner accepts loopback HTTP URLs only. Never point it
at a public site. See the complete setup, DB identity attestation, staff, and
local mail instructions in this guide after setup is finalized.

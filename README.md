# F2B SHOP

F2B SHOP is our IE4727 fashion e-commerce website project.

For the local-only order acknowledgement setup, see
[docs/local_email/README.md](docs/local_email/README.md).

## Project versions

- **Base Version:** the existing PHP/MySQL application in the repository root. Its source and browser tests remain unchanged.
- **Modern Version:** the parallel React 19, Express 5, and MongoDB application in [modern/](modern/). Setup instructions are in [modern/docs/SETUP_WINDOWS.md](modern/docs/SETUP_WINDOWS.md), and current stage results are in [modern/docs/STATUS.md](modern/docs/STATUS.md) and [modern/docs/PARITY_RESULTS.md](modern/docs/PARITY_RESULTS.md).

The Modern Version does not connect to a payment processor. Its optional receipt is local-only; see [docs/local_email/README.md](docs/local_email/README.md).

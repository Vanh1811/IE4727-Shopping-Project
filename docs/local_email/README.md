# Local order acknowledgement email

This optional feature sends a plain-text acknowledgement only after an order
and its item snapshots have been committed. It does not use the checkout
email as a recipient, process payments, or contact a cloud mail service.

## Safety boundary

The helper sends only when all of these are true:

- `F2B_LOCAL_MAIL_ENABLED` is exactly `1`.
- `F2B_LOCAL_MAIL_TO` and `F2B_LOCAL_MAIL_ALLOWED_TO` are valid addresses on
  a local-only domain (`.test`, `.localhost`, `.local`, or `localhost`).
- Both recipient values match exactly, ignoring letter case.
- PHP has a working local mail transport.

Use the address of your own configured local mailbox. The example
`student@f2b.test` is a placeholder, not an account that exists automatically.
Do not put a Gmail/Outlook address or any other public mailbox in either
variable. An allowlisted address does not make an SMTP relay safe: configure
the transport to accept mail locally and disable outbound relaying.

Mailpit is a convenient local capture tool. Its default SMTP listener is
`127.0.0.1:1025` and its inbox is `http://127.0.0.1:8025`; it captures messages
for inspection and is not necessarily the literal “own web account in your
local web server” required by the course. Confirm with the lecturer whether a
local capture inbox is acceptable. Do not claim course compliance based on
Mailpit alone. See the [Mailpit installation](https://mailpit.axllent.org/docs/install/),
[SMTP configuration](https://mailpit.axllent.org/docs/configuration/smtp/), and
[web UI](https://mailpit.axllent.org/docs/configuration/http/) documentation.

## Verify prerequisites

1. Start XAMPP Apache and MySQL. Confirm that the site connects to `f2b_shop`
   and PHP has `mysqli` enabled.
2. Check the PHP configuration used by the running Apache. From a matching
   PHP command prompt, run `php --ini`. Alternatively, use a temporary
   `phpinfo()` page reachable only through `127.0.0.1`, inspect its loaded
   configuration file, then delete the page immediately. Never publish a
   `phpinfo()` page.
3. Start a local SMTP receiver bound only to loopback, at `127.0.0.1:1025`.
   If using Mailpit, download its current binary from the official install
   guide and run it with its default listeners; no extra CLI options are
   required for these ports. Open `http://127.0.0.1:8025` for the capture
   inbox. Do not expose either listener on a public interface.
4. Confirm the SMTP port is listening locally. A TCP connection proves only
   that a listener is available; it does not prove PHP uses it or captured a
   message.

PHP's `mail()` returning true means the configured local transport accepted
the handoff. It is not proof that the message reached an inbox. Verify receipt
manually in the local mailbox or approved capture inbox.

## Windows XAMPP native PHP transport

On Windows, PHP can connect directly to the SMTP host configured under
`[mail function]`. In XAMPP Control Panel, choose **Apache → Config → php.ini**
and inspect the active `sendmail_path`. If it is unset, configure the local
receiver:

```ini
[mail function]
SMTP = 127.0.0.1
smtp_port = 1025
sendmail_from = no-reply@f2b.test
```

An active `sendmail_path` takes precedence and causes `SMTP`, `smtp_port`, and
`sendmail_from` to be ignored. Verify the effective configuration instead of
assuming the native SMTP settings are active. Save the file and restart
Apache. Do not change a global configuration without understanding which PHP
installation Apache loads.

Observed in the implementation environment on 29 October 2026: Windows,
XAMPP PHP CLI 8.0.30, loaded CLI ini `D:\xampp\php\php.ini`, `mysqli` and
`mail()` available, `SMTP=localhost`, `smtp_port=25`, `sendmail_path` empty,
and `sendmail_from` empty. TCP ports 80 and 3306 responded locally; 1025 and
8025 did not. Apache may load a different ini file, so check Apache's actual
configuration before relying on this observation. No mail receiver was
running during these checks.

## macOS and Linux XAMPP

PHP on Unix-like systems normally hands `mail()` to a sendmail-compatible
program through `sendmail_path`; the Windows `SMTP` and `smtp_port` settings
do not configure that transport. First identify the installed XAMPP location
and the PHP configuration file it actually loads. Do not assume a fixed
`/Applications/XAMPP` or `/opt/lampp` path.

If a sendmail-compatible bridge such as `msmtp` is already available, configure
it outside this repository to connect only to `127.0.0.1:1025`, with
authentication and TLS disabled only for that loopback test receiver. Verify
its config and executable path from the installed version, then point that
PHP installation's `sendmail_path` at the bridge in sendmail-compatible mode
(for example, `"/absolute/path/to/msmtp -t"`). `msmtp` documents sendmail mode
and the `-t` recipient option in its [manual](https://marlam.de/msmtp/msmtp.pdf).
This local bridge setup has not been executed on macOS/Linux in this
environment. If the bridge is absent, ask the local administrator to configure
a local-only transport; do not install or alter a privileged system mail
service as part of this project, and never point it to an external relay.

## Apache environment variables

Set these variables in the local Apache server or virtual-host configuration
(for example, the local `httpd-vhosts.conf`), not in committed PHP source:

```apache
SetEnv F2B_LOCAL_MAIL_ENABLED 1
SetEnv F2B_LOCAL_MAIL_TO student@f2b.test
SetEnv F2B_LOCAL_MAIL_ALLOWED_TO student@f2b.test
```

Replace the example with your own configured local test mailbox, and make the
two addresses identical. Keep the values scoped to the local development
virtual host, save the configuration, and restart Apache. Never use a public
email address. The mail helper fails closed if the recipient is malformed,
not on a local-only domain, or does not exactly match the allowlist.

To check that Apache's PHP process sees the variables without printing their
values, temporarily use a local-only diagnostic PHP file that prints only
`set` or `missing` for each variable. Open it through `127.0.0.1`, then delete
it. CLI environment checks do not prove Apache inherited the same variables.

## Local acceptance steps

1. Start Apache, MySQL, and the loopback-only local mail receiver.
2. Add two products with different sizes and colours to the cart.
3. Place an order using fake customer details. The checkout email field is
   required for the order form but is never the mail recipient.
4. Confirm the order and its item rows in MySQL. For a recent-order overview:

   ```sql
   SELECT id, full_name, status, total, email_sent_at
   FROM orders
   ORDER BY id DESC
   LIMIT 5;
   ```

   For one test order, inspect `product_name`, `size`, `colour`, `unit_price`,
   `quantity`, and `line_total` in `order_items` using that test order's ID.
5. Open your own local mailbox (or the approved local capture inbox) and
   compare every line, variant, quantity, discount, delivery fee, and total
   with persisted rows. Do not commit real customer data or access codes.
6. Check `email_sent_at`. Refresh the confirmation page and verify that it
   does not send again. The one-time guest access code is shown only on its
   first authorized confirmation display.
7. Disable `F2B_LOCAL_MAIL_ENABLED`, place another test order, and confirm the
   order is saved without a mail attempt.
8. In a disposable environment, test an invalid/unallowlisted recipient and
   an unavailable SMTP receiver. Confirm the order remains saved and that
   `email_sent_at` is not set when transport rejects the message.

The checkout flow does not add an idempotency key: repeating a valid checkout
POST can create two distinct orders. The 303 redirect prevents a normal page
refresh from resubmitting the form, but it does not protect against a repeated
POST. This behavior is separate from receipt refresh suppression.

The SMTP handoff and database stamp are separate operations. The helper sends
while holding a database transaction and records `email_sent_at` afterward;
if a transport accepts mail but the later database update or commit fails,
the message cannot be unsent and a later attempt may duplicate it. This is not
an exactly-once delivery guarantee.

## Troubleshooting

| Symptom | Probable cause | Check |
|---|---|---|
| Order created but mail disabled | Feature flag, recipient, or allowlist missing/mismatched | Check Apache variables are set without printing their values |
| `mail()` returns false | Wrong transport or receiver offline | Check Apache's loaded `php.ini`, `sendmail_path`, and loopback SMTP port |
| `mail()` returns true but inbox is empty | Handoff is not final delivery | Check local inbox, receiver logs, and configured local recipient |
| No mail on confirmation refresh | Intended duplicate prevention | Check `email_sent_at` and inbox message count |
| Receipt has no products | Saved rows missing or lookup problem | Inspect `order_items` for that test order ID |
| First confirmation hides mail status | Confirmation UI regression | Verify the separate email status panel |

## Runtime verification record

Source syntax and static checks are recorded in `TEST_PLAN.md`. Live XAMPP
checkout, database receipt comparison, and inbox delivery require the student's
local runtime; they are not claimed as passed by this implementation.

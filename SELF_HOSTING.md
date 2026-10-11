# Self-hosting and operations

This is the authoritative deployment and recovery guide. It replaces the old
separate production/Cloudflare guide.

## Release gate

The original project-file versioning migration defects have been repaired and
fresh/populated migration paths are covered on SQLite. A clean and populated
upgrade test on the exact production MySQL/MariaDB engine remains a release
gate. Do **not** treat the SQLite checks as proof of a production-engine upgrade.

SQLite is the verified development/test path. This guide documents the
operational prerequisites and the safe sequence once the database release gate
is closed; it does not claim that cPanel, Cloudflare, SMTP, or a live deployment
has been verified.

## Platform requirements

- PHP 8.4.1 or later for the current `composer.lock`; development verification
  currently uses PHP 8.5.11.
- Composer 2.x and the PHP extensions required by Laravel, the selected
  database driver, `mbstring`, `fileinfo`, and `zip`. MySQL and MariaDB require
  `pdo_mysql`; the verified SQLite path requires `pdo_sqlite`. Verify the web
  PHP runtime as well as CLI PHP. Admin storage formatting uses the existing
  application formatter and no longer requires `intl`.
- Node.js 22.12+ and npm 10+ in the frontend build environment. Install from
  `package-lock.json`, not a fresh unconstrained dependency resolution.
- A web server whose document root is the repository's `public/` directory.
- One application host with a writable `storage/framework/cache/` and private
  local storage. The current lifecycle lock is a local OS file lock; multiple
  hosts or object storage need shared coordination before use.
- Database and private-file backups that are captured and restored together.

## Configuration

Keep `.env` outside version control. At minimum set:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
LOG_LEVEL=warning
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_SCHEME=smtps
MAIL_PORT=465
MAIL_URL=null
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="SchoolShare"
```

Use `DB_CONNECTION=mariadb` for Laravel's MariaDB connection when that is the
selected engine; validate the full migration chain on that exact version.
The values above are a template, not live credentials. Quote credentials with
spaces or special characters according to dotenv syntax and restrict `.env`
permissions. `.env.example` deliberately retains development defaults; do not
deploy its debug/log-mail configuration unchanged.

Keep the application's existing `APP_KEY` when upgrading. OTP digests use this
key; rotating it invalidates pending codes as well as Laravel encrypted data.
Use a persistent cache shared across requests for rate limiting. The file store
fits the supported single-host deployment; do not use `array`, `null`, or a
failover configuration that silently falls back to an in-memory store.

Configure and verify a real SMTP transport. Development `log` mail writes the
OTP body to logs, and `array` mail does not deliver it to a mailbox. Neither is
a production delivery configuration. Protect logs, backups, and `.env` access.

For implicit TLS, use `MAIL_SCHEME=smtps` and your provider's port (usually 465).
For a provider using STARTTLS, use `MAIL_SCHEME=smtp` and port 587. The installed
Symfony transport negotiates STARTTLS automatically when OpenSSL is available
and the server advertises it; verify TLS and mailbox receipt with your provider.
`MAIL_ENCRYPTION=tls`/`ssl` has no effect in this application. Leave `MAIL_URL=null`
when using individual host/port/credential variables. A configured `MAIL_URL`
DSN overrides those values; inspect it securely if SMTP settings appear ignored.
For STARTTLS endpoints that must reject unencrypted connections, a provider DSN
may include `?require_tls=true`; do not disable certificate verification.

Use `php artisan config:show mail.default` and
`php artisan config:show mail.mailers.smtp.scheme` to inspect safe fields. Avoid
printing the complete mail/database configuration because it includes credentials.
Refresh `php artisan config:cache` after environment changes. Configure SMTP,
PHP, and proxy timeouts for synchronous delivery; no worker is required for the
current mail paths. Do not configure mail failover to `log`/`array` in production:
it can report success without delivering and can write private mail to logs.

OTP, collaborator invitations, and upgrade-instruction failure reports retain
the operation and exception class, but discard raw transport messages and
previous exceptions. The October 11 error-redaction migration also replaces
legacy stored transport errors. It does not remove them from existing logs or
backups; keep those restricted and apply your retention policy.

Set PHP's `upload_max_filesize` and `post_max_size` high enough for the
application's configured single-file and batch limits. The application defaults
to 100 MiB per file and 200 MiB per upload batch; proxy and PHP limits must not
be smaller than the intended request size.

`SCHOOLSHARE_MAX_STORAGE_GB` in `.env.example` is not the authoritative
per-account quota. Canonical per-plan limits are in `schoolshare.plans`; change
those values deliberately and keep product pricing copy aligned.

## Installation sequence after the database release gate closes

1. Back up the database and `storage/app/private` together. Confirm that the
   backup can be restored together in a non-production environment.
2. Deploy application code outside the web root and point the site document
   root to `public/`. Preserve one shared `storage/` directory across releases,
   including private/public files, file sessions/cache, and the lifecycle lock.
   Separate release directories must use that same lock path while overlapping.
3. Run `composer install --no-dev --optimize-autoloader` with the required PHP
   version.
4. Create `.env`, generate a key only for a new installation, and verify
   `APP_DEBUG=false`. Preserve the existing key for an upgrade.
5. Enter maintenance mode: `php artisan down`.
6. Run the database migration only after it has passed a clean and populated
   migration test on the target engine: `php artisan migrate --force`.
7. If upgrading legacy uploads, run `php artisan schoolshare:recalculate-storage`.
   It creates ledger records and reconciles charges; it does not delete files.
8. Only after inspecting its output, optionally run
   `php artisan schoolshare:recalculate-storage --cleanup`. This deletes
   unreferenced ledger-backed files and expired generated exports.
9. Build/cache only after configuration is correct:

   ```sh
   npm ci --ignore-scripts
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

10. Leave maintenance mode with `php artisan up`, then perform the smoke checks
    below.

If migration or reconciliation fails, keep maintenance mode enabled. Restore
the coordinated database/private-storage backup or repair the reported legacy
records first. Do not reset quota counters manually and do not delete private
objects to make the command succeed.

## Email-verification upgrade

The `2026_10_05_000004_protect_email_otp_verifiers` migration removes legacy
plaintext OTP rows and replaces them with email-bound keyed verifiers and
attempt/delivery counters. Apply it in maintenance mode with the other
migrations; accounts and verified email status are retained, but pending codes
are deliberately invalidated. Unverified users must request a new code. A
rollback also clears ephemeral tokens; it cannot restore the old codes.

Codes expire after 15 minutes and allow five incorrect guesses. Verification
allows ten submissions per account and thirty per IP per ten minutes. Both
resend routes share a three-request account limit and ten-request IP limit per
ten minutes. The database also permits three deliveries per account per ten
minutes, including registration delivery and failed sends; clearing the cache
does not reset that delivery budget. Registration permits five attempts per IP
per ten minutes. Configure trusted proxy addresses correctly before relying on
client-IP limits behind a reverse proxy.

Changing an email clears verification and issues a code for the new address.
If SMTP fails, registration/email changes remain saved and the OTP page shows
a retry warning. Correct the transport and use Resend code; exhausted budgets
require waiting for the ten-minute window. Delivery is synchronous and real
mailbox receipt still needs a deployment smoke test.

## File permissions and storage

The PHP/web-server account must be able to read/write `storage/` and
`bootstrap/cache/`. There is no portable `chmod` number that works for every
cPanel/VPS ownership model. Verify ownership and write access with the hosting
provider instead of applying recursive world-writable permissions.

`php artisan storage:link` exposes `storage/app/public` for public assets such
as avatars. It does not expose project uploads: those stay under
`storage/app/private` and are served through authorized routes.

Private project operations explicitly use `Storage::disk('local')`; merely
changing `FILESYSTEM_DISK` cannot migrate them to object storage. An ephemeral
platform such as Laravel Cloud or multiple hosts requires persistent shared
storage and coordinated locking changes before deployment. Database and private
file backups are mandatory; back up `storage/app/public` too if avatars must be
restored, and preserve `APP_KEY` securely for recovery.

## Identity and delivery-error data repairs

The October 11 `backfill_missing_usernames` migration assigns deterministic
`user_{id}` names to legacy null/empty/space-only identities, adding a numeric
suffix if that name is already taken. It leaves valid existing usernames alone
and can safely be rerun. Saving an account preserves an existing username when
the submitted value is blank; accounts still missing one receive a generated
name. Users can continue changing their username to an available, valid value.

`redact_upgrade_mail_delivery_errors` replaces stored non-null raw SMTP error
messages with a generic retry diagnostic. Both repairs retain their results
on rollback: a rollback cannot reconstruct missing names or restore sensitive
messages. Rehearse both on the target engine and review the assigned identities
in staging before applying the full migration chain in maintenance mode.

## Cloudflare and reverse proxies

Cloudflare is optional and has not been integration-tested for this application.
If used, terminate TLS safely at the origin (Full/Strict rather than Flexible),
avoid caching authenticated HTML or `Set-Cookie` responses, and retain the
origin's request-size/time limits.

Do not add `TRUSTED_PROXIES=*` on the assumption that the application will read
it; the current bootstrap does not configure proxy trust from that variable.
Configure trusted proxies explicitly only after selecting the trusted proxy
addresses and testing real client-IP, HTTPS, session, and rate-limit behavior.

## Operational checks

Before accepting a deployment:

- Verify a guest is redirected from protected pages and a new account reaches
  OTP verification before the dashboard.
- Verify owner/editor/viewer/private-project access with test accounts.
- Upload a small text file; edit it; compare its versions; download it; export
  the current project ZIP; and restore a checkpoint within the documented
  limitations.
- Confirm private files cannot be fetched unauthenticated.
- Check `storage/logs/laravel.log`, available private-disk capacity, and mail
  delivery with a real test mailbox.
- Verify an email change sends a new code, the previous code/link is rejected,
  resend/guess limits return 429, and a failed delivery can be retried.
- Verify admin dashboard, populated user list/detail, combined search/plan
  filters, and plan changes. A blank profile username must retain working
  project/profile links. Repeated upgrade submissions must reuse one pending
  request; concurrently exercise this on the selected production engine.
- Run the focused regression commands in [README.md](README.md#local-development).

## Backups, retention, and recovery

Back up database metadata and `storage/app/private` as one unit. A database-only
backup cannot restore content; a disk-only backup cannot restore permissions,
versions, or billing ownership. Test restores, including a ledger reconciliation,
before relying on the system.

The cleanup command is an explicit destructive operation. It retains any blob
still referenced by a file version, but deletes unreferenced ledger-backed data.
Keep a backup for the retention period you promise users before running it.
Generated exports are retained for at most one hour on normal cleanup paths.

## Known deployment limitations

- No production database migration/upgrade run has been completed for this
  revision.
- No queue worker, scheduler, monitoring, alerting, SMTP, CDN, or multi-node
  deployment has been validated.
- Large/long-running operations are bounded synchronously; individual stalled
  filesystem/database calls still need web-server and PHP timeouts.
- Registry audits cover the locked build dependencies, not separately loaded
  CDN assets or future advisories. Recheck both before each release.

# Self-hosting and operations

This is the authoritative deployment and recovery guide. It replaces the old
separate production/Cloudflare guide.

For a **new cPanel installation**, use the ready-to-upload package and
[browser installation guide](docs/INSTALLATION_CPANEL.md). That wizard is
fresh-install only and must not be used to upgrade an existing site.

## Release gate

Local staging now uses PHP 8.5.11, MySQL 8.4.11, MariaDB 11.4.13, and upstream
MariaDB 10.11.19 with the required extensions. Clean/populated migration and
coordinated recovery checks have passed on all three targets. The HTTP
acceptance tests exercise Apache, actual
private/public disks, persistent sessions/cache, captured SMTP, and concurrent
upgrade requests. See the current evidence in
[the repository review](docs/REPOSITORY_REVIEW_2026-10-05.md).

This is **local staging evidence**, not a public production release. The sample
domain is `schoolshare.example`; it is not a registered domain or a TLS/mail
configuration. A selected host, real domain/HTTPS/proxy settings, provider SMTP
and actual mailbox receipt, off-host backup retention, live alert delivery, and
green remote CI checks remain required before deployment. Mailpit captures mail
locally; additional authenticated STARTTLS/SMTPS tests prove local encrypted
transport only, never provider authentication, DNS, or mailbox deliverability.

## Platform requirements

- PHP 8.5 for this supported staging/deployment target. The current lockfile's
  minimum is PHP 8.4.1; verification uses PHP 8.5.11.
- Composer 2.x and the PHP extensions required by Laravel, the selected
  database driver, `mbstring`, `fileinfo`, and `zip`. MySQL and MariaDB require
  `pdo_mysql`; the verified SQLite path requires `pdo_sqlite`. Install `intl`
  for the production-equivalent runtime. Verify the web
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
APP_URL=https://schoolshare.example
LOG_LEVEL=warning
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
SCHOOLSHARE_MINIMUM_FREE_MB=1024

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

`BackupRestorationTest` performs a real engine dump, packages private bytes with
checksums, restores into `schoolshare_restore_testing`, and verifies retained
charges and restoration of a soft-deleted checkpoint file. Its temporary backup
is deliberately removed after the drill. It is not a scheduled production backup
or an off-host backup copy. Production backups also need encrypted, restricted
off-host retention, public avatars, and separately preserved `APP_KEY`/secrets.
Put the application in maintenance mode and stop all writers while capturing
the database and disks. `--single-transaction` alone does not coordinate a disk
snapshot with database writes.

The staging pass repaired historical migration portability: a replacement
foreign-key support index before the versioning conversion drops its composite
index; short snapshot index names; collation-aware collision-safe name repair;
and a foreign-key support index when reverting the OTP unique constraint.
Already-applied migrations do not rerun. These are pre-release portability
repairs, not a repair command for a partially failed deployed schema. Restore
its coordinated backup or review its exact schema first; do not blindly retry
or use `migrate:fresh` on an existing application database.

## Repeatable isolated staging

The root `Dockerfile` builds a no-dev production Apache image and a separate
PHP test image. The pinned images reproduce PHP 8.5.11 / MySQL 8.4.11 /
MariaDB 11.4.13 and upstream MariaDB 10.11.19. The latter matches the cPanel
server's upstream version, not its exact CloudLinux build; actual-host acceptance
is still required. Update pins deliberately and rerun acceptance for upgrades.
`compose.staging.yaml` uses its own project name and volumes. Database and SMTP
ports are internal; HTTP ports 18080/18081/18082 and Mailpit UI port 18025 are bound
only to loopback. Do not publish this sample stack as production: it uses local
HTTP cookies, an unauthenticated SMTP capture service with fault injection, and
test-database privileges. Never populate it with production secrets or data.

Prepare the test dependencies with `composer install --no-interaction`. Generate
`.env.staging` once, independently of `.env`, and keep its mode 0600:

```sh
if [ ! -f .env.staging ]; then
    php -r 'file_put_contents(".env.staging", "STAGING_APP_KEY=base64:".base64_encode(random_bytes(32))."\nSTAGING_DB_PASSWORD=".bin2hex(random_bytes(24))."\nSTAGING_DB_ROOT_PASSWORD=".bin2hex(random_bytes(24))."\n");'
fi
chmod 600 .env.staging
dc() { docker compose --env-file .env.staging -f compose.staging.yaml "$@"; }
dc build app test
dc up -d --wait mysql mariadb mariadb-cpanel mailpit
dc exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS schoolshare_testing; CREATE DATABASE IF NOT EXISTS schoolshare_restore_testing; GRANT ALL ON schoolshare_testing.* TO schoolshare; GRANT ALL ON schoolshare_restore_testing.* TO schoolshare;"'
dc exec -T mariadb sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS schoolshare_testing; CREATE DATABASE IF NOT EXISTS schoolshare_restore_testing; GRANT ALL ON schoolshare_testing.* TO schoolshare; GRANT ALL ON schoolshare_restore_testing.* TO schoolshare;"'
dc exec -T mariadb-cpanel sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -uroot -e "CREATE DATABASE IF NOT EXISTS schoolshare_testing; CREATE DATABASE IF NOT EXISTS schoolshare_restore_testing; GRANT ALL ON schoolshare_testing.* TO schoolshare; GRANT ALL ON schoolshare_restore_testing.* TO schoolshare;"'
dc run --rm --user root --entrypoint sh test -c 'mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; chown -R www-data:www-data storage bootstrap/cache'
dc up -d app app-mariadb app-cpanel
```

Run each engine sequentially because the local test containers share scratch
storage. These destructive tests are restricted to the dedicated test databases;
HTTP acceptance creates only disposable records in `schoolshare_staging`:

```sh
for engine in mysql mariadb mariadb-cpanel; do
    driver=mysql
    app_service=app
    if [ "$engine" = mariadb ]; then driver=mariadb; app_service=app-mariadb; fi
    if [ "$engine" = mariadb-cpanel ]; then driver=mariadb; app_service=app-cpanel; fi
    dc run --rm -e DB_CONNECTION="$driver" -e DB_HOST="$engine" -e LOG_CHANNEL=null test test --compact --exclude-group=staging,recovery,installation,package
    dc run --rm -e DB_CONNECTION="$driver" -e DB_HOST="$engine" -e STAGING_INSTALLATION=1 -e LOG_CHANNEL=null test test --compact tests/Feature/InstallationAcceptanceTest.php
    dc run --rm -e DB_CONNECTION="$driver" -e DB_HOST="$engine" -e STAGING_RECOVERY=1 test test --compact tests/Feature/BackupRestorationTest.php
    dc exec -T --user www-data "$app_service" php artisan migrate --force --no-interaction
    dc exec -T --user www-data "$app_service" php artisan optimize --no-interaction
    dc exec -T --user www-data "$app_service" php artisan schoolshare:health --no-interaction
    dc run --rm -e DB_CONNECTION="$driver" -e DB_HOST="$engine" -e STAGING_ACCEPTANCE=1 -e STAGING_BASE_URL="http://$app_service" test test --compact tests/Feature/StagingAcceptanceTest.php
done
dc up -d scheduler
```

The extracted-package acceptance runs on the build host, which needs Docker
access, PHP 8.5 with ZIP, the test dependencies, OpenSSL CLI, and built
`public/build` assets. Run engines sequentially with loopback ports 18443 and
18026 available:

```sh
for engine in mysql mariadb; do
    STAGING_PACKAGE=1 STAGING_PACKAGE_ENGINE="$engine" vendor/bin/phpunit tests/Feature/CpanelPackageAcceptanceTest.php
done
STAGING_PACKAGE=1 STAGING_PACKAGE_ENGINE=mariadb-cpanel STAGING_PACKAGE_PUBLIC_DIRECTORY=schoolshare.ethionext.com.et STAGING_PACKAGE_MAIL_SCHEME=smtps vendor/bin/phpunit tests/Feature/CpanelPackageAcceptanceTest.php
```

This builds and extracts an isolated cPanel layout using the running production
image's no-dev vendor tree. It tests HTTPS setup, CSRF, actual migrations,
authenticated STARTTLS (or implicit TLS on port 465 for the cPanel target),
OTP/admin access, private-path denial, and optimized caches. Random dedicated
test databases, containers, archives, and certificates
are cleaned afterward; it does not use production credentials or a real cPanel
account. Do not run it on a public/production Docker host.

Do not regenerate the staging key or delete its database/storage volumes between
releases. `dc down` stops the stack but retains data; do not add `--volumes`
without intentionally abandoning the entire disposable staging environment.
The sample application URL uses `schoolshare.example`; access the loopback ports
directly or map that sample hostname to loopback for local navigation. No DNS
changes or HTTPS certificate are created by these commands.

## CI and monitoring

`.github/workflows/ci.yml` runs locked dependency audits, PHP/JavaScript tests,
the asset build, and separate MySQL/MariaDB staging gates on pushes and pull
requests. Actions and container images are pinned. The workflow does **not**
deploy. Its hosted run and repository branch-protection requirements must be
enabled/confirmed after committing and pushing; local execution is not evidence
that GitHub has run the workflow.

Keep `/up` as a boot/liveness check. `/ready` verifies database connectivity,
cache round-trip, private-disk writes, runtime directory permissions, and free
space, returning only `{"status":"ok"}` (200) or `{"status":"unavailable"}`
(503) with `Cache-Control: no-store`. It does not probe SMTP or backups. Docker
checks readiness every 30 seconds; an unhealthy status does not automatically
restart a still-running container.

`php artisan schoolshare:health` provides labelled local diagnostics, returns a
nonzero exit status on failure, and logs only failing check names. The production
scheduler runs it every minute. Start `dc up -d scheduler` for the supported
single-host Docker setup; on another host, configure one scheduler to execute
`php artisan schedule:run` every minute. No mail worker is required. Set
`SCHOOLSHARE_MINIMUM_FREE_MB` for the host's capacity/traffic; its default is
100 MiB, not a quota or reservation. The Docker PHP profile uses 128 MiB per file,
256 MiB request bodies, a 120-second PHP execution limit, and a 15-second default
socket timeout. Configure matching proxy limits and review synchronous SMTP
timeouts on the actual host.

Before release, configure an external uptime monitor for the real HTTPS `/ready`
endpoint, an alert recipient/channel, container/service failures, disk/database
capacity and backup-age alerts. Trigger a failure and prove the alert reaches
its recipient. Scheduled logs and local health status are implemented; an
external notification service is not configured by this sample stack.

## Known deployment limitations

- Local MySQL/MariaDB migration/recovery and captured SMTP checks do not prove
  a real hosted upgrade, public-domain mail delivery, or off-host recovery.
- Remote CI, real HTTPS/proxy settings, external alert delivery and production
  backup scheduling remain unverified. CDN and multi-node behavior are outside
  the local gate; only the single-host local-storage deployment is supported.
- Large/long-running operations are bounded synchronously; individual stalled
  filesystem/database calls still need web-server and PHP timeouts.
- Registry audits cover the locked build dependencies, not separately loaded
  CDN assets or future advisories. Recheck both before each release.

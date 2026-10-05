# Self-hosting and operations

This is the authoritative deployment and recovery guide. It replaces the old
separate production/Cloudflare guide.

## Release gate

Do **not** deploy this revision to a new MySQL or MariaDB environment yet. The
repository review reproduced failures in the existing project-file versioning
migration on MariaDB and with populated legacy data. The checked-in migration
chain must be repaired and tested on the exact production engine before this
guide can become a production-install procedure.

SQLite is the verified development/test path. This guide documents the
operational prerequisites and the safe sequence once the database release gate
is closed; it does not claim that cPanel, Cloudflare, SMTP, or a live deployment
has been verified.

## Platform requirements

- PHP 8.4.1 or later for the current `composer.lock`.
- Composer 2.x and the PHP extensions required by Laravel, the selected
  database driver, `mbstring`, `fileinfo`, and `zip`.
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

DB_CONNECTION=your-supported-driver
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=...
MAIL_FROM_ADDRESS=...
```

Set PHP's `upload_max_filesize` and `post_max_size` high enough for the
application's configured single-file and batch limits. The application defaults
to 100 MiB per file and 200 MiB per upload batch; proxy and PHP limits must not
be smaller than the intended request size.

`SCHOOLSHARE_MAX_STORAGE_GB` in `.env.example` is not the authoritative
per-account plan quota today. Plan-key and quota-policy reconciliation remain
open work; do not advertise a plan limit solely from that environment value.

## Installation sequence after the database release gate closes

1. Back up the database and `storage/app/private` together. Confirm that the
   backup can be restored together in a non-production environment.
2. Deploy application code outside the web root and point the site document
   root to `public/`.
3. Run `composer install --no-dev --optimize-autoloader` with the required PHP
   version.
4. Create `.env`, run `php artisan key:generate`, and verify `APP_DEBUG=false`.
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

## File permissions and storage

The PHP/web-server account must be able to read/write `storage/` and
`bootstrap/cache/`. There is no portable `chmod` number that works for every
cPanel/VPS ownership model. Verify ownership and write access with the hosting
provider instead of applying recursive world-writable permissions.

`php artisan storage:link` exposes `storage/app/public` for public assets such
as avatars. It does not expose project uploads: those stay under
`storage/app/private` and are served through authorized routes.

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
- Group 4 OTP hardening and Group 5 frontend dependency maintenance remain
  outstanding security work.

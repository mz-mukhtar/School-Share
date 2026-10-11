# Install SchoolShare on cPanel

This guide is for a **new, empty installation** using the standard browser
wizard. You create the database in cPanel; SchoolShare creates its tables and
first administrator. Do not use this ZIP or wizard to upgrade an existing site.
For upgrades, coordinated backups, recovery, and operational requirements, use
[SELF_HOSTING.md](../SELF_HOSTING.md).

## Before you upload

Confirm your hosting account provides:

- PHP **8.5**, with `intl`, `pdo_mysql`, `zip`, `fileinfo`, `mbstring`, and OpenSSL.
- **MySQL 8.4, MariaDB 11.4, or MariaDB 10.11.19+ within the 10.11 line**. Ask the host for its actual version; cPanel
  does not let an application install or upgrade the database server.
- HTTPS with a valid certificate for your domain, Apache rewrite support,
  and permission to create the public avatar-storage symbolic link.
- One application host, enough disk space, outbound SMTP access, and cPanel
  Cron Jobs or an equivalent scheduler.

The installer refuses incompatible PHP/database versions. CLI PHP must also be
8.5 for cron/maintenance. Do not assume a matching domain PHP version means the
default command-line PHP is the same.

Have your production SMTP provider's host, port, encryption, username/password,
and authorized sender address ready. Set up the provider's required sender-domain
DNS records. Do not use the local staging Mailpit configuration in production.

`schoolshare.example` is a placeholder, not a usable public domain. Substitute
your real domain everywhere. No production credentials are included in the ZIP.

The packaged layout has been tested on isolated PHP 8.5 Apache hosting with
MySQL 8.4, MariaDB 11.4, and upstream MariaDB 10.11.19, HTTPS, and authenticated
STARTTLS/SMTPS capture (implicit TLS on port 465 for the tailored package).
That is not an actual cPanel account or production SMTP deliverability test;
complete the real-host checks below before opening the site publicly.
The CloudLinux `10.11.19-MariaDB-cll-lve` build reported by your host is a
provider variant; local tests use the matching upstream version, not that exact
provider build. PHP 8.4 shown inside phpMyAdmin is not your domain's PHP version.

## 1. Create the empty database

In cPanel, open **Database Wizard** (older versions: MySQL Database Wizard):

1. Create a database dedicated to this new SchoolShare installation.
2. Create a database user with a strong password.
3. Assign that user to the database with the privileges needed to create and
   migrate its tables; cPanel's ALL PRIVILEGES option applies to that database,
   not the entire database server.
4. Save the **full** database and user names shown by cPanel. They may include
   your account prefix, such as `account_schoolshare` and `account_schooluser`.
5. Ask the host for the database hostname and port. `localhost:3306` is common,
   but not universal. Do not use the database server's root account.

See the [official cPanel Database Wizard guide](https://docs.cpanel.net/cpanel/databases/mysql-database-wizard/).
Existing tables or views cause SchoolShare setup to refuse the database. No SQL
dump needs importing manually.

## 2. Upload and extract the release ZIP

Use the generated **cPanel release ZIP**, not GitHub's source ZIP. Production
dependencies and frontend assets are already included; Composer/npm are not
needed on the hosting account for normal installation.

Choose the package whose public folder matches the domain's existing document
root. For **schoolshare.ethionext.com.et**, use
**`schoolshare-ethionext-cpanel-mariadb-10.11.19-2026-10-11.zip`**, not the older
generic `public_html` package. Your main website already uses `public_html` and
must not be overwritten or selected as SchoolShare's document root.

In cPanel File Manager, enable **Show Hidden Files**. Confirm the private
`schoolshare` folder does not already exist and the SchoolShare document root
is empty. Upload/extract the tailored ZIP into the account's home directory,
**outside all website document roots**. The result must be:

```text
/home/ACCOUNT/
├── schoolshare/             private application; do not rename
│   ├── .cpanel-layout
│   ├── app/, vendor/, config/, storage/
│   └── artisan
├── schoolshare.ethionext.com.et/  existing SchoolShare document root
│   ├── index.php
│   ├── .htaccess
│   └── build/
└── public_html/             existing main website; leave unchanged
```

The application folder and public folder must be siblings. Never place
`schoolshare`, `vendor`, `.env`, backups, or private storage inside any website's
document root, including `schoolshare.ethionext.com.et` or `public_html`.
Do not extract over an existing website or overwrite its files. Use a dedicated
account/site or have an operator prepare an empty destination first.
Keep the subdomain access-restricted with provider-supported controls until
installation and the real-host smoke/backup/alert checks are complete.

Both the public front controller and Laravel's web/CLI public path are configured
for the tailored ZIP's exact layout. Keep the existing SchoolShare domain root
shown by cPanel, and verify its full filesystem path in File Manager. Do not
rename folders or nest the entire package inside that public directory.
The generic package still targets `public_html` and is only for a dedicated,
empty site/account. A different layout requires a newly configured package and
acceptance tests, not just moving or renaming files. cPanel normally does not allow
changing the main domain's document root through its domain interface.
See [cPanel's domain management documentation](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/110/).

Remove the uploaded ZIP from the hosting account after extracting it. Keep a
trusted release copy locally. Do not leave downloadable application archives
under the public directory.

Compare the downloaded ZIP's SHA-256 with the maintainer's release digest before
uploading. Keep the release copy unchanged; each installation generates its own
private configuration and does not modify that archive.

## 3. Open and unlock setup

1. Select PHP 8.5 for the domain and enable its valid HTTPS certificate.
2. Open the domain in your browser **using HTTPS**. The site redirects to
   `/install`. Plain HTTP setup is refused.
3. First launch creates a unique application key in private `schoolshare/.env`
   and a random token in private `schoolshare/setup-token.txt`.
4. In cPanel File Manager, read `schoolshare/setup-token.txt` and paste its
   64-character token into the wizard. Do not put the token in a URL, send it
   publicly, or copy the private file into the web root.

Unlocking lasts 30 minutes in that browser session. Invalid attempts are rate
limited. The wizard must work before any database tables exist; it uses private
file sessions/cache during setup.

If bootstrap fails, ask the host to correct ownership/write access for the
private configuration directory, `storage`, and `bootstrap/cache`. The PHP
process needs write access as your hosting user. **Do not use 777 permissions.**

## 4. Complete the wizard

After unlocking:

1. Fix every failed requirements check. The public directory must allow the
   installer to create the avatar-storage link.
2. Enter the site name and real HTTPS address, without a subdirectory, query,
   or fragment. The name configures the application/mail sender, not commercial
   white-label licensing.
3. Choose the actual database engine and enter the full cPanel database/user
   names, password, host, and port.
4. Enter SMTP settings. Use **SMTPS** for implicit TLS (usually port 465), or
   **SMTP / required STARTTLS** for a STARTTLS provider (usually port 587).
   Certificate validation is not disabled. Use your provider's actual settings.
5. Enter the first admin's name, lowercase email, and a password of at least
   12 characters containing uppercase, lowercase, a number, and a symbol.
6. Click **Install SchoolShare once**, and keep the page open until it finishes.

For the hosting settings you supplied, choose **MariaDB**, database host
`localhost`, port `3306` (confirm with your provider), and the full database/user
names created in cPanel. Enter the site address `https://schoolshare.ethionext.com.et`
only after its certificate is valid. Use these SMTP settings from Connect Devices:

| Field | Value |
| --- | --- |
| Scheme | `smtps` / implicit TLS |
| Host | `schoolshare.ethionext.com.et` |
| Port | `465` |
| Username / sender address | `notifications@schoolshare.ethionext.com.et` |
| Password | The mailbox password, entered privately in the wizard |
| Sender name | SchoolShare |

Do not use the non-SSL host/port or your cPanel login password. Check the sender
domain's SPF/DKIM status in Email Deliverability and prove real OTP receipt.
Use an email you regularly read for the administrator; it need not be the
notifications mailbox. Do not share mailbox, database, or administrator passwords.

The installer tests the database connection/version and refuses a nonempty
database. It then saves production configuration, runs migrations, creates one
administrator, permanently locks setup, and attempts to send the admin's OTP.
Passwords/tokens are never redisplayed or flashed as old form input. No demo
users are seeded. Debug stays off; file sessions/cache and synchronous mail are
configured for this supported single-host deployment.

Installation completion is not proof of SMTP deliverability. A failed email
does not undo the administrator or reopen setup. Sign in, correct the private
SMTP configuration if needed, then use **Resend code** on the verification page.
Do not run installation again to fix mail.

## 5. Sign in and verify the administrator

Sign in with the credentials you chose and enter the six-digit email code.
Once verified, open `/admin` on the same site. Normal registrations are not
administrators. There is no shared/default admin password.

The installer sets `SCHOOLSHARE_ADMIN_EMAIL` to that administrator's address for
upgrade-request notifications. You can change the recipient in the private `.env`
later; rebuild configuration caches if they have been enabled.

The panel provides account/plan statistics, user search/details and plan changes,
plus manual upgrade approval/rejection. Approving changes the account's plan;
it does not verify payment or create an automated subscription. Review the
existing payment instructions/contact details before offering paid upgrades.

On completion, `schoolshare/storage/app/installed.lock` is written, setup is
disabled in `.env`, and `setup-token.txt` is removed. Visiting `/install` should
return 404. Preserve the installation markers and application key in backups.

## 6. Finish operational setup before opening registration publicly

- In cPanel Cron Jobs, schedule **once per minute**:

  ```text
  /PATH/TO/PHP85 /home/ACCOUNT/schoolshare/artisan schedule:run
  ```

  Ask the host for its PHP 8.5 CLI path and replace ACCOUNT. Do not run duplicate
  schedulers. Capture cron failures in restricted logs or operator alerts.
- Check `/ready` returns HTTP 200. Configure external HTTPS readiness, error,
  disk-space and backup-age alerts, and prove alert delivery.
- Schedule coordinated database, private-file and public-avatar backups with
  encrypted/restricted off-server retention. Preserve `.env`/APP_KEY securely
  and prove a restore on this host. Database-only backups are insufficient.
- Run registration/OTP, private-file permissions, uploads/avatar, ZIP, checkpoint,
  and admin smoke checks through the real domain and mail provider.
- Confirm `/schoolshare/.env`, `/vendor/autoload.php`, `/setup-token.txt`, and
  private upload paths are not publicly accessible. Keep PHP body/time limits
  appropriate for your upload limits; ask the host for any proxy limits.
- Require green release CI. Hosted CI, real-host TLS/SMTP, backups and alerts are
  separate gates; a successful wizard does not replace them.

## If installation is interrupted

If a request times out or setup reports that installation started, **do not
resubmit, remove lock files, drop tables, or rotate APP_KEY**. MySQL/MariaDB schema
changes cannot be rolled back as one transaction. Keep the configuration and
`installation-started.lock` for diagnosis. An operator must inspect the exact
schema or restore the coordinated backup. If this was an unused fresh attempt,
an operator can prepare an entirely new empty destination/database after
confirming no user data exists; the browser wizard does not perform that cleanup.

If an installed site's `.env` is missing, restore its original configuration and
key. The package refuses to bootstrap setup again when installation markers
exist. Future releases must use the upgrade procedure, not the fresh-install ZIP.

## Maintainer: build a new package

Use PHP 8.5/Composer on a build machine. Prepare a separate vendor directory with
`composer install --no-dev` from the exact `composer.lock`, and run `npm ci
--ignore-scripts` followed by `npm run build` in the repository. Never replace the
development vendor directory just to package a release.

Then run:

```sh
php artisan schoolshare:package --vendor=/absolute/path/to/production/vendor --output=/absolute/private/path/schoolshare-cpanel.zip --no-interaction
```

For this existing subdomain root, add
`--public-directory=schoolshare.ethionext.com.et` and choose a new output filename.
The builder adjusts both PHP entry points/public-path configuration, refuses
unsafe/nested folder names, and includes no `public_html` entries in that variant.

The output filename must be new and outside the public directory. The command
prints its SHA-256 digest and refuses development dependencies, mismatched
locked package versions, missing built assets, and existing output files. It
packages allowlisted runtime paths, empty runtime directories, and the cPanel
front controller, not `.env`, setup tokens, populated databases, user files,
compiled caches, development tooling, or public storage links. Each deployed
copy generates its own key/token at first launch.

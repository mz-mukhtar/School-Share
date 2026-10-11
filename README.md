# SchoolShare

SchoolShare is a server-rendered Laravel application for student projects. It
organises files into projects, records checkpoint-based file versions, supports
private collaboration, and provides browser previews for selected file types.

The live project website is [SchoolShare](https://schoolshare.ethionext.com.et).
[EthioNext](https://ethionext.com.et) is the technology solutions provider behind
the application; its company website covers its broader services, not the
SchoolShare sign-in or installation workflow.

## Start here

| Goal | Guide |
| --- | --- |
| Install a new site on cPanel | [cPanel installation](docs/INSTALLATION_CPANEL.md) |
| Operate, upgrade, or recover an existing site | [Self-hosting and operations](SELF_HOSTING.md) |
| Understand the application | [Architecture](docs/ARCHITECTURE.md) and [web routes](docs/API.md) |
| Contribute a change | [Contribution guide](CONTRIBUTING.md) |
| Report a vulnerability privately | [Security policy](SECURITY.md) |
| Review release evidence and outstanding work | [Repository review](docs/REPOSITORY_REVIEW_2026-10-05.md) |

## Deployment status

Local migration, recovery, installer, and HTTP acceptance checks have passed on
the documented MySQL/MariaDB targets. On 11 October 2026, the operator also
confirmed a cPanel installation, SMTP authentication, OTP receipt, and access
to the verified account dashboard. That is operator-reported hosting evidence,
not an independent audit of production.

Hosted CI enforcement, off-host backup restoration, external alert delivery,
and the complete real-host permission/upload/recovery checklist still require
confirmation. A successful installation does not close those operational gates.

## Current capabilities

- Create public or private projects; private projects are visible to their owner
  and current collaborators.
- Upload checkpoint files, organise them in folders, view the current project
  file tree, and download the current project as one ZIP archive.
- View images, media, PDFs, and bounded text/code files. PDF preview uses
  PDF.js 6.4.299 with evaluation disabled.
- Edit bounded text/code files in the browser; an edit creates another version.
- Compare bounded text/code versions. Office-document diffing is intentionally
  unavailable in the request process.
- Restore the complete recorded file/folder tree of a checkpoint, including
  files later renamed, moved, or deleted; collaborate as an owner, editor, or
  viewer.
- Follow users, browse public projects after OTP verification, and use the
  authenticated activity feed.
- Choose Light, Dark, or System appearance from the header. The browser remembers
  the choice; System follows the operating system's color preference.

## Appearance

Shared page colors live in `public/theme.css`; the preference controller is
`public/theme.js`. Bootstrap layouts load these directly, not through Vite.
Keep both assets with the updated Blade views when deploying a theme change.
Preferences are browser-local, not account settings. With storage blocked, a
selection applies to the current page; without JavaScript, pages use a readable
dark fallback and hide the inactive selector.

## Important current limits

- A project ZIP contains current file versions only. There is no historical
  checkpoint ZIP endpoint.
- Snapshot manifests are created for new checkpoints. A legacy checkpoint made
  before the manifest migration cannot be restored safely and returns a clear
  error rather than partially changing the project.
- Office preview depends on Google Docs Viewer fetching the application's file
  URL. Authenticated/private files cannot be fetched by Google; download those
  files instead.
- Storage accounting, project ZIPs, uploads, restores, forks, and edits are
  bounded for the current single-host private-local-disk design. See the
  architecture and deployment guides for operational limits.
- OTP delivery requires a working mail transport and persistent rate-limit
  cache. The hardened token schema must be migrated before deploying this code.

## Plans and storage

| Plan | Storage per account | Owned projects | Collaborators per project |
| --- | --- | --- | --- |
| Free | 3 GB | 15 | 5 |
| Pro | 10 GB | 20 | 20 |
| Custom | 10 GB | 20 | 20 |

These are the current defaults in `config/schoolshare.php`. Storage is calculated
in binary units: the interface's 3 GB allowance is 3,221,225,472 bytes (3 GiB).
Retained file versions count toward the account's storage; a checkpoint restore
reuses retained bytes rather than charging for another copy. The 100 MiB
single-file and 200 MiB upload-batch/export limits are separate from that quota.

Upgrades are administrator-reviewed requests, not automated payments or
subscriptions. Custom branding requires the applicable commercial permission;
the application does not validate commercial entitlement. See [LICENSE.md](LICENSE.md).

## Runtime requirements

The checked-in lock file currently resolves Laravel 13.34.0 and requires PHP
8.4.1 or later. Use the locked dependency graph, not the historical Laravel 11
or PHP 8.1 instructions from older releases.

| Component | Current requirement/status |
| --- | --- |
| PHP | 8.5 for supported deployment and the installer; the dependency lockfile minimum is 8.4.1 |
| Composer | 2.x |
| Frontend build environment | Node.js 22.12+ and npm 10+; use `package-lock.json` with `npm ci` |
| Database for local development/testing | SQLite is covered by the test suite |
| Supported installer database lines | MySQL 8.4, MariaDB 11.4, or MariaDB 10.11.19+ within the 10.11 line |
| Locally tested versions | PHP 8.5.11, MySQL 8.4.11, MariaDB 11.4.13, and upstream MariaDB 10.11.19; hosting-provider builds require their own acceptance |
| Private uploads | `storage/app/private` on the `local` disk |
| Public assets | `storage/app/public` for public assets such as avatars; `storage:link` exposes that disk only |
| Required PHP extensions | Laravel/Composer platform requirements plus `intl`, `mbstring`, `fileinfo`, and `zip`; `pdo_mysql` for MySQL/MariaDB or `pdo_sqlite` for SQLite; admin storage formatting itself does not require `intl` |

## Local development

Use SQLite for the currently verified local path:

The following commands are for a new development checkout. Do not overwrite
an existing `.env`, regenerate an installed site's key, or use production data.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Set the following values in `.env`:

```ini
DB_CONNECTION=sqlite
DB_DATABASE="/absolute/path/to/School Share/database/database.sqlite"
```

Then run:

```sh
php artisan migrate
php artisan storage:link
npm ci --ignore-scripts
npm run build
php artisan serve
```

Run the relevant test file for a change. The security regression commands are:

```sh
php artisan test --compact tests/Feature/ProjectPreviewSecurityTest.php \
  tests/Feature/ProjectPrivacyTest.php \
  tests/Feature/ActivityVisibilityTest.php \
  tests/Feature/ProjectBoundaryTest.php \
  tests/Feature/StorageLifecycleSecurityTest.php \
  tests/Feature/ResourceBoundsSecurityTest.php \
  tests/Feature/Auth/OtpSecurityTest.php \
  tests/Feature/ProfileTest.php \
  tests/Feature/AdminUserManagementTest.php \
  tests/Feature/UpgradeRequestTest.php \
  tests/Feature/MailDeliverySecurityTest.php \
  tests/Feature/ReleaseDataRepairTest.php
npm test
npm run audit:security
composer audit --locked
```

For the full repository suite, run `php artisan test --compact`. Opt-in engine,
recovery, and package tests require the isolated setup in the operations guide;
a normal test run skips them. Test results are dated in the repository review.

The opt-in theme browser check requires installed Chromium, localhost listening
permission, and access to the existing Bootstrap/font CDNs:

```sh
THEME_BROWSER=1 node tests/JavaScript/ThemeBrowser.test.mjs
```

It renders real Laravel route responses with factory data, in-memory SQLite,
fake storage, and no outgoing mail, then checks both themes at desktop/mobile
widths. Screenshots and the report are written to a fresh temporary directory.
This is a rendered-page UI check, not a production or complete end-to-end audit.

OTP codes expire after 15 minutes and are stored as keyed digests bound to the
account and current email. Five incorrect guesses disable a code. Verification
and delivery have account/IP rate limits, and a database-backed delivery budget
allows three sends per account per ten minutes, including initial delivery and
failed sends. An email change clears verification and issues a fresh code;
delivery failures preserve the account/change and offer a resend path.

The locked Vite/Tailwind build is separate from the Bootstrap, PDF.js, and
CodeMirror CDN assets used by the current pages. An npm audit covers the locked
build dependencies, not those external assets.

Production configuration is in [SELF_HOSTING.md](SELF_HOSTING.md#configuration).
Set production/debug/HTTPS/session values explicitly and provide a real SMTP
transport using `MAIL_SCHEME` (not `MAIL_ENCRYPTION`). Keep private local storage,
sessions, and rate-limit cache persistent across releases on the single host.
The October 11 migrations repair missing legacy usernames and redact stored mail
errors; apply them with the full migration chain after staging validation.

New cPanel installations can use the standard browser wizard and packaged
production dependencies; see [the installation guide](docs/INSTALLATION_CPANEL.md).
This fresh-install flow does not replace the production operational gates or
the backup-first upgrade procedure.

Repeatable local staging and engine acceptance are documented in
[SELF_HOSTING.md](SELF_HOSTING.md#repeatable-isolated-staging). The sample domain
is `schoolshare.example`; the stack does not provision real DNS, HTTPS, or a mail
provider. GitHub Actions release gates, Docker readiness checks, and scheduled
health diagnostics are included. Public operation requires green hosted CI,
verified HTTPS/provider mailbox delivery, off-host backups, and live alerts.
The operator has confirmed the initial cPanel/mail workflow; the remaining
acceptance checks are listed above.

## Storage and recovery

New private objects are recorded in a `stored_blobs` ledger with a billing
owner and byte count. Fresh installations run the complete migration chain.
Existing installations must reconcile legacy uploads as part of a reviewed
upgrade; local recovery drills do not prove a production restore.

After a coordinated backup and only after the database migration chain is
proven on the selected production engine, the intended migration sequence is:

```sh
php artisan down
php artisan migrate --force
php artisan schoolshare:recalculate-storage
php artisan up
```

Do not run `--cleanup` casually: it deletes unreferenced ledger-backed objects
and expired generated exports. The complete recovery guidance is in
[SELF_HOSTING.md](SELF_HOSTING.md).

## Documentation

- [Self-hosting and operations](SELF_HOSTING.md)
- [Architecture and data model](docs/ARCHITECTURE.md)
- [Web route reference](docs/API.md)
- [Security policy](SECURITY.md)
- [Contributing](CONTRIBUTING.md)
- [Repository review and remediation status](docs/REPOSITORY_REVIEW_2026-10-05.md)
- [Changelog](CHANGELOG.md)

## License

The repository includes the custom [SchoolShare Community License](LICENSE.md),
with attribution and commercial-use restrictions. Do not describe these terms
as an unrestricted open-source license. Composer metadata currently declares
MIT; that inconsistency requires a decision by the copyright holder before
distribution. This documentation update does not change the license terms.

## Security reports

Do not open a public issue for a vulnerability. See [SECURITY.md](SECURITY.md)
for the private reporting channel and scope.

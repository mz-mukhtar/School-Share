# SchoolShare

SchoolShare is a server-rendered Laravel application for student projects. It
organises files into projects, records checkpoint-based file versions, supports
private collaboration, and provides browser previews for selected file types.

This repository is actively being remediated. Read the [repository review](docs/REPOSITORY_REVIEW_2026-10-05.md) before treating a feature as production-ready.

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
- Restore current logical files to a previous checkpoint and collaborate as an
  owner, editor, or viewer.
- Follow users, browse public projects after OTP verification, and use the
  authenticated activity feed.

## Important current limits

- A project ZIP contains current file versions only. There is no historical
  checkpoint ZIP endpoint.
- Checkpoint restore does not yet reconstruct a complete historical folder/file
  snapshot after a file has been renamed, moved, or deleted. Do not use it as
  your sole recovery mechanism.
- Office preview depends on Google Docs Viewer fetching the application's file
  URL. Authenticated/private files cannot be fetched by Google; download those
  files instead.
- Storage accounting, project ZIPs, uploads, restores, forks, and edits are
  bounded for the current single-host private-local-disk design. See the
  architecture and deployment guides for operational limits.
- OTP attempt/resend hardening and JavaScript dependency maintenance remain
  open security work (Groups 4 and 5 in the review).

## Runtime requirements

The checked-in lock file currently resolves Laravel 13.34.0 and requires PHP
8.4.1 or later. Use the locked dependency graph, not the historical Laravel 11
or PHP 8.1 instructions from older releases.

| Component | Current requirement/status |
| --- | --- |
| PHP | 8.4.1+ for the current lock file |
| Composer | 2.x |
| Database for local development/testing | SQLite is covered by the test suite |
| Production MySQL/MariaDB | Not yet supported for a clean deployment: the versioning migration requires repair and an upgrade test on the chosen engine |
| Private uploads | `storage/app/private` on the `local` disk |
| Public assets | `storage/app/public` for public assets such as avatars; `storage:link` exposes that disk only |
| Required PHP extensions | At least `mbstring`, `fileinfo`, and `zip`; enable the extensions required by your chosen database/PHP build |

## Local development

Use SQLite for the currently verified local path:

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Set the following values in `.env`:

```ini
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/School Share/database/database.sqlite
```

Then run:

```sh
php artisan migrate
php artisan storage:link
php artisan serve
```

Run the relevant test file for a change. The Group 1–3 regression suite is:

```sh
php artisan test --compact tests/Feature/ProjectPreviewSecurityTest.php \
  tests/Feature/ProjectPrivacyTest.php \
  tests/Feature/ActivityVisibilityTest.php \
  tests/Feature/ProjectBoundaryTest.php \
  tests/Feature/StorageLifecycleSecurityTest.php \
  tests/Feature/ResourceBoundsSecurityTest.php
node tests/JavaScript/PdfPreview.test.mjs
```

The wider suite has two pre-existing failures documented in the review: a
registration redirect expectation and a factory fixture with an unset storage
counter. They must be fixed rather than accepted as a production pass.

## Storage and recovery

New private objects are recorded in a `stored_blobs` ledger with a billing
owner and byte count. The migration and reconciliation command have not been
run against a production database or existing uploads.

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

The intended source-license terms are in [LICENSE.md](LICENSE.md). The Composer
package metadata currently says MIT while that license is a restrictive,
custom SchoolShare Community License. That is a legal/metadata conflict that
the copyright holder must resolve before distribution; this README does not
assert either as the controlling license.

## Security reports

Do not open a public issue for a vulnerability. See [SECURITY.md](SECURITY.md)
for the private reporting channel and scope.

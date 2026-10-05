# Contributing to SchoolShare

Thanks for contributing. This repository is a Laravel 13 application with
security and data-integrity remediation in progress. Read the
[repository review](docs/REPOSITORY_REVIEW_2026-10-05.md) before changing file,
checkpoint, access-control, migration, OTP, or deployment behavior.

## Local setup

Use PHP 8.4.1+ with Composer 2.x. SQLite is the currently verified local
database path:

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Set `DB_CONNECTION=sqlite` and `DB_DATABASE` to the absolute SQLite path, then:

```sh
php artisan migrate
php artisan storage:link
php artisan serve
```

Do not claim MySQL/MariaDB support in a change unless the clean and populated
migration chain has been tested on the intended engine.

## Change expectations

- Keep project content on the private `local` disk (`storage/app/private`).
  `storage:link` is for public assets such as avatars, not project blobs.
- Use current project-scoped routes and access helpers; do not reintroduce
  global nested-resource lookups or unscoped folder IDs.
- Treat the blob ledger as the source of storage charges. A file write, copy,
  deletion, fork, restore, or account deletion must preserve its lifecycle and
  compensation behavior.
- Do not expose private files merely to make an external preview service work.
- Do not add a dependency, deployment target, or license claim without maintainer
  approval.
- Update documentation whenever a visible feature, route, operation limit,
  storage contract, or deployment requirement changes.

## Verification

Run the narrowest relevant test first. For a security/storage/change touching
the Group 1–3 work, run:

```sh
php artisan test --compact tests/Feature/ProjectPreviewSecurityTest.php \
  tests/Feature/ProjectPrivacyTest.php \
  tests/Feature/ActivityVisibilityTest.php \
  tests/Feature/ProjectBoundaryTest.php \
  tests/Feature/StorageLifecycleSecurityTest.php \
  tests/Feature/ResourceBoundsSecurityTest.php
node tests/JavaScript/PdfPreview.test.mjs
vendor/bin/pint --dirty --format agent
```

The complete PHP suite currently contains two documented baseline failures: a
registration redirect expectation and a profile test fixture missing a storage
default. Do not treat those as an acceptable release state; fix or update them
when your work reaches them.

## Pull requests

1. Create a focused branch (`fix/...`, `docs/...`, `test/...`, or `refactor/...`).
2. Explain the user-visible behavior and security/data implications.
3. Include tests for new decisions, validation, access boundaries, and failure
   paths where applicable.
4. State the commands run and their result.
5. Update `CHANGELOG.md` under Unreleased for material user-facing changes.

Use conventional commit prefixes such as `feat:`, `fix:`, `docs:`, `test:`,
`refactor:`, and `chore:`.

## Security reports

Do not put vulnerability details in a public issue. Follow
[SECURITY.md](SECURITY.md).

# Contributing to SchoolShare

SchoolShare welcomes focused contributions that improve reliability,
accessibility, security, and the student project workflow. This is a Laravel 13
application. Read the
[repository review](docs/REPOSITORY_REVIEW_2026-10-05.md) before changing file,
checkpoint, access-control, migration, OTP, or deployment behavior.

The public application is [SchoolShare](https://schoolshare.ethionext.com.et).
[EthioNext](https://ethionext.com.et) is the technology solutions provider's
company site. Test contributions on an isolated installation, not the public
application or the company's other websites.

## Local setup

Use PHP 8.5, Composer 2.x, and Node.js 22.12+/npm 10+ for the supported development
workflow. SQLite is suitable for local development; use the isolated staging
stack to verify MySQL/MariaDB behavior:

Start from a new checkout. Keep existing private configuration and application
keys intact, and use synthetic data rather than production uploads or dumps.

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
npm ci --ignore-scripts
npm run build
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
- Keep OTP issuance/verification in `EmailOtpService`; preserve email binding,
  locking, single-use behavior, persistent delivery budgets, and sanitized mail
  failure reporting. Never store plaintext OTPs or flash them to the session.
- Commit `package-lock.json` with intentional dependency updates and verify a
  clean `npm ci --ignore-scripts`, build, tests, and audit. Do not use forced
  audit upgrades without reviewing their compatibility.
- Do not add a dependency, deployment target, or license claim without maintainer
  approval.
- Update documentation whenever a visible feature, route, operation limit,
  storage contract, or deployment requirement changes.

## Verification

Run the narrowest relevant test first. For project access, file handling, or
storage-lifecycle changes, run:

```sh
php artisan test --compact tests/Feature/ProjectPreviewSecurityTest.php \
  tests/Feature/ProjectPrivacyTest.php \
  tests/Feature/ActivityVisibilityTest.php \
  tests/Feature/ProjectBoundaryTest.php \
  tests/Feature/StorageLifecycleSecurityTest.php \
  tests/Feature/ResourceBoundsSecurityTest.php
npm test
vendor/bin/pint --dirty --format agent
```

For verification/profile changes, run
`php artisan test --compact tests/Feature/Auth/OtpSecurityTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/ProfileTest.php`.
Before handing off broad changes, run `php artisan test --compact` and
`npm run build`. Dependency changes also require `npm run audit:security` and
`composer audit --locked`. Ordinary PHP runs skip opt-in staging, recovery,
installation, and package cases; use the [operations guide](SELF_HOSTING.md#repeatable-isolated-staging)
for those checks. Document skipped tests and do not imply a local result proves
production readiness. Pure documentation changes require link, formatting,
example, and sensitive-content checks rather than new behavior tests.

## Documentation and sensitive information

- Use reserved example domains and clearly marked placeholders in generic
  instructions. Public project contacts are not login credentials.
- Never commit `.env`, live passwords, application/license keys, setup tokens,
  session URLs, OTPs, customer data, database dumps, or private log excerpts.
- Redact screenshots and support transcripts before sharing them. If a secret
  has already been disclosed, rotate or revoke it; deleting text is not enough.
- Preserve the distinction between historical findings, current limitations,
  local test evidence, and operator-reported hosting checks.
- Keep command examples scoped to the intended environment. Flag destructive
  operations and require coordinated backups for production changes.
- Keep `AGENTS.md` and `CLAUDE.md` aligned; they contain contributor-tooling
  policy and are not installation prerequisites.

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

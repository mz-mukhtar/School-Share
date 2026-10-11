# Changelog

All notable user-facing, security, and operational changes are recorded here.
The project uses Keep a Changelog-style sections; version tags should be added
only when a release is actually cut.

## Unreleased — updated 2026-10-11

### Release preparation — 2026-10-11

- Updated locked development dependencies to `concurrently` 10.0.6 and
  `shell-quote` 1.12.0 to resolve the critical command-injection advisory.
- Preserve existing usernames on blank saves and backfill missing legacy route
  identities with a collision-safe, repeatable data migration.
- Reuse application storage formatting in admin pages without `intl`, fix the
  plan-edit form route, group combined search/plan filters, and distinguish
  custom plans from yearly billing in the user-management labels.
- Lock the account before finding/creating a pending upgrade; retries reuse the
  same request. Real concurrent acceptance on the chosen engine remains required.
- Standardize redacted OTP/invitation/upgrade mail failures without chaining raw
  exceptions, and add a migration to redact previously stored upgrade errors.
- Add committed identity, admin, upgrade retry, mail-failure, and repair-migration
  regressions. Correct production SMTP scheme/DSN, session, extension, persistent
  storage, and upgrade instructions in the existing guides.

### Security and access control

- Escaped raw README HTML and blocked unsafe Markdown links.
- Replaced the vulnerable PDF.js integration with pinned PDF.js 6.4.299 and
  disabled PDF evaluation.
- Scoped project search, activity visibility, nested route binding, private
  starring, folder validation, comments, and collaborator forms.
- Hardened OTP verification with keyed, email/account-bound digests, single-use
  consumption, expiry, guess caps, and shared account/IP throttles. Added a
  persistent delivery budget that includes initial and failed sends.
- Email changes invalidate prior verification and send a new code. SMTP errors
  preserve account/profile changes with a retry warning and sanitized reporting.
  Legacy verification routes now share the OTP flow; already-issued signed
  links are still accepted only for the current email and clear outstanding OTPs.
- Added the protected-token migration; pending legacy codes are invalidated and
  must be resent after deployment.
- Added a reproducible npm lockfile and migrated the build to Tailwind 4's Vite
  plugin, removing the advisory-bearing legacy `braces` chain. Clean install,
  build, JavaScript tests, and npm/Composer audits pass at remediation time.

### Storage and file operations

- Added a private-blob billing ledger, lifecycle locking, quota reservations,
  reconciliation, and guarded cleanup.
- Bounded uploads, editor content, README rendering, text diffs, project size,
  exports, forks, and folder depth.
- Hardened ZIP paths and rejected unsafe legacy archive entries.
- Restricted synchronous diffs to bounded text/code input; Office diffing is
  unavailable pending an isolated processing design.
- Repaired the versioning migration conversion path and added canonical global
  project slugs, plan limits, collaborator limits, immutable version MIME data,
  and root-level uniqueness constraints.
- Added checkpoint snapshot manifests and recoverable logical-file deletion so
  restore reproduces a prior folder tree after rename, move, or deletion.
- Preserved foreign-project checkpoint history when a contributor account is
  deleted, with anonymized attribution.
- Wired upgrade routes/state transitions and retryable delivery status; a
  collaborator invitation can be resent after a delivery failure.

### Documentation

- Rewrote product, deployment, architecture, route, security, and contribution
  documentation against the current code.
- Consolidated production guidance into `SELF_HOSTING.md`.
- Removed the obsolete `ROADMAP.md`.

### Known release blockers

- A clean and populated MySQL/MariaDB deployment test is still required on the
  chosen production engine.
- Real SMTP delivery and production cache/proxy configuration still require
  deployment verification; audits do not cover separately loaded CDN assets.

## 0.1.0 — 2026-10-04

Initial project scaffolding and early documentation. Historical claims in this
release section describe that release and should not be read as the current
support matrix; consult Unreleased and the repository review.

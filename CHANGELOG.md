# Changelog

All notable user-facing, security, and operational changes are recorded here.
The project uses Keep a Changelog-style sections; version tags should be added
only when a release is actually cut.

## Unreleased — updated 2026-10-11

### Website identity

- Distinguish the live SchoolShare application at `schoolshare.ethionext.com.et`
  from EthioNext's technology solutions company website at `ethionext.com.et`.
- Centralize project/company branding links, remove duplicate footer addresses,
  and use the current installation for landing-page metadata and email site links.
- Correct the landing page's cPanel guide link and remove assumptions about a
  separate company pricing page. Add rendered-page and email-link regressions.

### Storage allowance

- Increase the Free plan allowance from 1 GiB to 3 GiB (displayed as 3 GB).
  Existing Free accounts read the updated plan configuration without a data
  migration. Pro and Custom remain at 10 GiB.
- Add quota regressions for existing accounts, usage above the old allowance,
  the exact new boundary, and rejected uploads that must not write data.

### Release preparation — 2026-10-11

- Add isolated upstream MariaDB 10.11.19 staging for the actual cPanel target,
  guarded 10.11.19+ release-line support, and a matching CI matrix entry.
- Allow a validated public-folder name in cPanel packaging; configure both web
  and CLI public paths for the SchoolShare subdomain without packaging or
  overwriting the main website's `public_html` directory.
- Extend extracted-package acceptance to authenticated implicit TLS on port 465
  and preservation of a neighbouring main-site folder; update the host-specific
  installation guide without embedding production credentials.
- Add a fresh-install cPanel ZIP builder and HTTPS browser wizard with a private
  owner token, hosting/version checks, empty-database protection, migrations,
  one chosen administrator, existing OTP verification, and permanent setup locks.
- Package locked production dependencies and built assets without credentials,
  user data, or development caches; document the standard cPanel installation.
- Verify extracted packages on both engines over HTTPS with real CSRF/session
  handling, authenticated STARTTLS, admin verification, and cached operation.
- Make upgrade-notification recipients configurable; fresh setup selects the
  chosen administrator instead of the original application's hardcoded address.
- Add isolated PHP 8.5 / MySQL 8.4 / MariaDB 11.4 Docker staging with persistent
  local disks, production Apache configuration, and an SMTP capture/fault service.
- Fix production-engine migration blockers: foreign-key support indexes,
  oversized snapshot index names, and collation-aware legacy-name collisions.
- Add clean/populated engine migration, coordinated database/private-file
  recovery, real HTTP/SMTP retry, role/upload/avatar/ZIP/checkpoint, and parallel
  upgrade-request acceptance tests.
- Add pinned GitHub Actions release gates, minimal `/ready` diagnostics, Docker
  health checks, and a scheduled `schoolshare:health` command. External alerts
  and hosted CI must still be configured and confirmed before production.

- Updated locked development dependencies to `concurrently` 10.0.6 and
  `shell-quote` 1.12.0 to resolve the critical command-injection advisory.
- Preserve existing usernames on blank saves and backfill missing legacy route
  identities with a collision-safe, repeatable data migration.
- Reuse application storage formatting in admin pages without `intl`, fix the
  plan-edit form route, group combined search/plan filters, and distinguish
  custom plans from yearly billing in the user-management labels.
- Lock the account before finding/creating a pending upgrade; retries reuse the
  same request. Independent-session concurrent acceptance now passes locally
  on both supported production engines.
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
- Audit all first-party Markdown and related documentation for credentials,
  unsafe examples, route omissions, stale evidence, and product inconsistencies.
- Align storage, admin, installation, privacy, and operational guidance with
  current source; distinguish operator-reported cPanel/SMTP success from local
  acceptance and unconfirmed production checks.
- Mark the original cPanel ZIP as a historical artifact that predates the
  3 GiB allowance. New source requires a separately built and tested release.
- Preserve source-license terms and the historical security findings; improve
  their presentation without claiming unresolved issues have been fixed.

### Known release blockers

- The operator reported initial cPanel installation and SMTP/OTP success on
  11 October 2026. Hosted CI enforcement, coordinated off-host restoration,
  external alert delivery, and complete real-host smoke acceptance still need
  confirmation before broad public use.
- Source-license metadata and ZIP-attribution inconsistencies require maintainer
  resolution before distribution; this documentation pass does not change terms.
- Audits do not cover separately loaded CDN assets or representative load testing.

## 0.1.0 — 2026-10-04

Initial project scaffolding and early documentation. Historical claims in this
release section describe that release and should not be read as the current
support matrix; consult Unreleased and the repository review.

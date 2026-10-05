# Changelog

All notable user-facing, security, and operational changes are recorded here.
The project uses Keep a Changelog-style sections; version tags should be added
only when a release is actually cut.

## Unreleased — 2026-10-05

### Security and access control

- Escaped raw README HTML and blocked unsafe Markdown links.
- Replaced the vulnerable PDF.js integration with pinned PDF.js 6.4.299 and
  disabled PDF evaluation.
- Scoped project search, activity visibility, nested route binding, private
  starring, folder validation, comments, and collaborator forms.

### Storage and file operations

- Added a private-blob billing ledger, lifecycle locking, quota reservations,
  reconciliation, and guarded cleanup.
- Bounded uploads, editor content, README rendering, text diffs, project size,
  exports, forks, and folder depth.
- Hardened ZIP paths and rejected unsafe legacy archive entries.
- Restricted synchronous diffs to bounded text/code input; Office diffing is
  unavailable pending an isolated processing design.

### Documentation

- Rewrote product, deployment, architecture, route, security, and contribution
  documentation against the current code.
- Consolidated production guidance into `SELF_HOSTING.md`.
- Removed the obsolete `ROADMAP.md`.

### Known release blockers

- The MySQL/MariaDB versioning migration chain still needs a clean and populated
  production-engine repair/test.
- OTP hardening and frontend dependency maintenance remain open security work.
- Full historical checkpoint-tree restore is not implemented.

## 0.1.0 — 2026-10-04

Initial project scaffolding and early documentation. Historical claims in this
release section describe that release and should not be read as the current
support matrix; consult Unreleased and the repository review.

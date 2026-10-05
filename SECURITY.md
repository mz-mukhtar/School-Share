# Security policy

## Reporting a vulnerability

Do not open a public GitHub issue for a suspected vulnerability.

- Email: mahizeki037@gmail.com
- Phone: +251 992 194 042
- Website: https://ethionext.com.et

Include the affected version/commit, impact, reproducible steps, a safe proof
of concept, and any suggested mitigation. Please avoid accessing other users'
data or disrupting the service while testing.

The stated response targets are acknowledgement within 48 hours, investigation
of critical reports within 7 days, and other reports within 30 days. These are
targets, not a guarantee of a particular fix or disclosure date.

## Scope

Report authentication/authorization bypasses, cross-project access, stored or
reflected script execution, unsafe archive/file handling, quota/accounting
bypasses, sensitive-data disclosure, request forgery bypasses, injection, and
meaningful denial-of-service conditions. Third-party dependency vulnerabilities
are relevant when the dependency is used by this application; include the
affected version and reachability details.

## Current controls

- Laravel session authentication, CSRF middleware, password hashing, and
  server-side validation are in use.
- Protected project routes require authentication and OTP verification.
- Project/checkpoint/file/folder/comment/collaborator routes use scoped
  project binding and application access checks.
- README Markdown escapes raw HTML and disables unsafe links; Blade escaping is
  still context-specific and must not be treated as a universal sanitizer.
- PDF preview is pinned to PDF.js 6.4.299 and disables evaluation.
- Private project blobs are kept outside the public web root. Upload names and
  ZIP entries are validated against traversal, and current ZIP export validates
  legacy rows too.
- Storage blobs have a billing ledger, lifecycle lock, bounded operations, and
  reconciliation command once the ledger migration is deployed.
- Named rate limits apply to uploads, editing, previews, diffs, forks, and
  downloads.

## Known limitations and release status

The migration chain has been repaired and is covered on SQLite; a clean and
populated test on the selected production MySQL/MariaDB engine remains a
release gate. Group 4 OTP hardening and Group 5 frontend dependency maintenance
are open. The local-disk lifecycle lock is single-host only. New checkpoints
have complete snapshot manifests; legacy checkpoints without a manifest are not
restored.

See [docs/REPOSITORY_REVIEW_2026-10-05.md](docs/REPOSITORY_REVIEW_2026-10-05.md)
for verified findings, fixed groups, outstanding work, and deployment limits.

## Researcher expectations

Act in good faith: minimise data access, do not publish a proof of concept
before a reasonable remediation window, and do not use social engineering or
physical access. Good-faith reporting will not be pursued as a legal claim by
the project maintainer, subject to applicable law and the boundaries above.

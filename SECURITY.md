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
- OTPs are stored as keyed SHA-256 digests bound to the account and current
  email, expire after 15 minutes, and stop accepting guesses after five failures.
  Successful verification consumes the token; an email change invalidates it.
- OTP verification is limited to ten submissions per account and thirty per IP
  per ten minutes. Resend routes share account/IP limits and a persistent
  database-backed three-delivery budget, including initial and failed sends.
  Registration has an additional five-attempt IP limit per ten minutes.
- Mail failures leave accounts/email changes recoverable, and reported OTP mail
  errors omit transport details. OTP input is not flashed back to the session
  and the code is not included in the mail subject.
- Frontend build dependencies are locked in `package-lock.json`. The Tailwind 4
  Vite integration removes the vulnerable legacy `braces` dependency chain;
  registry audits must be rerun as advisories change.

## Known limitations and release status

The migration chain has been repaired and is covered on SQLite; a clean and
populated test on the selected production MySQL/MariaDB engine remains a
release gate. The protected OTP migration must be deployed; it intentionally
invalidates pending legacy codes so users must resend. Use a working SMTP
transport and persistent cache, not the development log/array mailers or an
in-memory rate-limit cache. npm audits do not cover separately loaded CDN
assets. The local-disk lifecycle lock is single-host only. New checkpoints
have complete snapshot manifests; legacy checkpoints without a manifest are not
restored.

See [docs/REPOSITORY_REVIEW_2026-10-05.md](docs/REPOSITORY_REVIEW_2026-10-05.md)
for verified findings, fixed groups, outstanding work, and deployment limits.

## Researcher expectations

Act in good faith: minimise data access, do not publish a proof of concept
before a reasonable remediation window, and do not use social engineering or
physical access. Good-faith reporting will not be pursued as a legal claim by
the project maintainer, subject to applicable law and the boundaries above.

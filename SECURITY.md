# Security policy

## Reporting a vulnerability

Do not open a public GitHub issue for a suspected vulnerability.

- Private reporting email: mahizeki037@gmail.com
- SchoolShare project website: https://schoolshare.ethionext.com.et
- EthioNext company website: https://ethionext.com.et

Include the affected version/commit, impact, reproducible steps, a safe proof
of concept, and any suggested mitigation. Do not include live credentials,
OTPs, session cookies, private user files, or unredacted logs. Use a disposable
account and synthetic data; avoid accessing other users' data or disrupting
the service while testing.

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
- Mail failures leave accounts/email changes, collaborators, and upgrade requests
  recoverable. OTP, invitation, and upgrade-instruction reports omit raw transport
  details and previous exceptions; stored upgrade errors are redacted too. The
  October 11 migration redacts legacy stored messages, not existing logs/backups.
  OTP input is not flashed back to the session
  and the code is not included in the mail subject.
- Frontend build dependencies are locked in `package-lock.json`. The Tailwind 4
  Vite integration removes the vulnerable legacy `braces` dependency chain;
  registry audits must be rerun as advisories change.

## Known limitations and release status

The documented SQLite and MySQL/MariaDB migration/recovery checks passed in
isolated environments. The operator confirmed the initial cPanel and OTP/mail
workflow on 11 October 2026. Hosted CI enforcement, off-host restore drills,
external alert delivery, and full real-host acceptance remain unconfirmed.
Neither those checks nor a dependency audit constitute a penetration test.

- Deploy the full migration chain under a backup-first procedure. The protected
  OTP migration invalidates pending legacy codes; users must request new ones.
- Use authenticated, certificate-validated production SMTP and persistent
  rate-limit cache. Development `log`/`array` mailers and in-memory stores are
  not production configurations.
- Private files belong outside the web root. The supported storage lock is
  single-host; distributed/object-storage deployment requires design changes.
- Dependency audits exclude separately loaded CDN assets. Review their versions,
  availability, integrity, and data exposure independently.
- Legacy checkpoints without snapshot manifests cannot be safely restored.
- White-label configuration is not commercial entitlement validation. Licensing
  metadata and ZIP attribution inconsistencies are tracked in the review; they
  are not substitutes for application authorization controls.

## Credentials and incident handling

Keep live environment files, SMTP/database passwords, application keys, license
keys, setup tokens, backups, and logs out of source control and public web roots.
Documentation examples must use placeholders, not reusable credentials.

If a credential is exposed, restrict further access and rotate or revoke the
affected credential at its issuer. Update the private application configuration
and refresh its configuration cache. Preserve a restricted incident record.
Removing a file or Git commit alone does not revoke a disclosed secret.
Treat an exposed `APP_KEY` as a separate incident: plan rotation around encrypted
data and recovery requirements rather than generating a new key indiscriminately.
Never bypass OTP verification or edit verification timestamps to resolve SMTP
delivery failures; repair SMTP and use the supported resend flow.

## Privacy and retention boundaries

Public profiles, follower lists, and public-project metadata are visible by
design. Private-project content requires current project access. Public avatars
are served from the public disk; project files use authorized application routes.
Collaborators can download accessible content, and editors can modify/delete
project content and history. Private visibility is not a restriction on copies
already downloaded by authorized users.

Retained versions and checkpoint references can keep a deleted logical file's
bytes available and billable. Cleanup is reference-aware; backups may retain
older data according to the operator's retention policy. The operator must
publish applicable privacy/retention terms before accepting user data.

CDNs receive browser asset requests. The external Office viewer may receive a
file URL but cannot fetch authenticated downloads; never expose private content
to make preview work. SMTP providers process verification and notification mail.

See [docs/REPOSITORY_REVIEW_2026-10-05.md](docs/REPOSITORY_REVIEW_2026-10-05.md)
for verified findings, fixed groups, outstanding work, and deployment limits.

## Researcher expectations

Act in good faith: minimise data access, do not publish a proof of concept
before a reasonable remediation window, and do not use social engineering or
physical access. Good-faith reporting will not be pursued as a legal claim by
the project maintainer, subject to applicable law and the boundaries above.

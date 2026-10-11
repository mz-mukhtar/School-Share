# SchoolShare repository review — current reassessment

Updated: 11 October 2026

Reviewed application base: `990758c`, plus the October 11 working-tree staging,
migration-portability, monitoring, CI, fresh-install wizard, packaging, and
documentation changes described below.
This verification pass has not committed changes or made a public deployment.

Original pre-remediation baseline: `c6bf19b`

Scope: current source, migrations, configuration, repository tests, dependencies,
documentation, isolated production-mode Apache staging, and extracted cPanel
packages on MySQL 8.4, MariaDB 11.4, and the additional upstream MariaDB 10.11.19
cPanel target. The existing development environment, database, and uploads
were preserved. Real HTTP workflows were tested, not a complete browser UI run.

## Current assessment

**The local staging gate passes on PHP 8.5, MySQL 8.4, MariaDB 11.4, and upstream MariaDB 10.11.19;
public production acceptance remains open.** Release repairs R01–R05 pass the
repository tests on all three database targets. CI and monitoring are implemented,
but hosted CI execution and external alerts have not been verified.
The standard cPanel fresh-install ZIP and owner-unlocked HTTPS wizard are now
built and verified locally; an actual cPanel account still needs acceptance.
The tailored SchoolShare subdomain ZIP also passes an exact-artifact HTTPS/SMTPS
installation check without touching a neighbouring main-site folder (R08).
The October 5 engineering rating was 7.5/10, up from the original 4/10; that
historical score is retained below and has not been recalculated for this pass.

The repository is substantially stronger than the baseline. Security Groups
1–5 have been remediated in code, storage ownership and checkpoint snapshots
have explicit domain services, the build dependencies are reproducible, and
the committed regression suite passes. The previous XSS, privacy leaks,
archive traversal, broken checkpoint/diff/comment paths, plaintext OTPs, and
frontend advisory chain must not be presented as unchanged current findings.

The October 11 repairs preserve/backfill missing usernames, remove the admin
formatter's `intl` dependency, repair its plan-edit route, group admin search
filters, lock pending upgrade creation, and standardize redacted delivery errors
for OTP/invitation/upgrade mail. A newly reported critical npm advisory was also
resolved by updating `concurrently` and `shell-quote` within the existing ranges.
This staging pass additionally found and repaired foreign-key support-index
removal, overlong snapshot unique-index names, and collation-sensitive legacy
name collisions. Clean/populated upgrades, coordinated backup restoration,
captured SMTP failure/retry, and simultaneous upgrade submissions now pass on
both engines. Provider mail, HTTPS/proxy behavior, off-host backups, and hosted
operations remain separate production gates.

## Verification evidence

These checks were rerun for this reassessment unless explicitly marked as
earlier evidence:

| Check | Current result | Boundary of the evidence |
| --- | --- | --- |
| Repository PHP suite | **277 passed; 1,962 assertions** on SQLite, MySQL 8.4.11, MariaDB 11.4.13, and upstream MariaDB 10.11.19 | The full suite reports 292 cases, with 15 opt-in staging/recovery/installation/package cases skipped; all 15 are run separately per database target below. Ordinary regressions retain their storage/mail fakes |
| Clean/populated migration tests | **6 passed; 58 assertions per engine** (included above) | Isolated databases only: legacy version pointers/bytes, retained billing, root uniqueness, case/accent/trailing-space collation, and pre-existing rename suffixes |
| Coordinated backup restoration | **1 passed; 22 assertions per engine** | Actual database dump/import plus private-file ZIP and SHA-256 checks; restored reconciliation, download, and checkpoint behavior. Temporary test backups removed; not an off-host production backup drill |
| Production-mode HTTP acceptance | **4 passed; 136 assertions per engine** | Apache, real disks and sessions, captured SMTP/451 failure/retry, OTP consumption, roles, uploads/avatar, ZIP, checkpoint restoration, and independent-session parallel upgrade submissions; not real-provider delivery or browser rendering |
| Installer engine acceptance | **9 passed; 100 assertions per engine** | Actual empty-database migrations/admin/OTP state, wrong credentials/engine, existing tables/views, interrupted/concurrent setup, failed migrations/mail, and private/public path guards; temporary configuration with fake or deliberately failing mail |
| Extracted cPanel-package acceptance | **1 passed; 48 assertions** on each original engine; **1 passed; 50 assertions** on MariaDB 10.11.19 | Generic package uses required STARTTLS; tailored subdomain package uses authenticated implicit TLS on port 465. HTTPS/CSRF/secure sessions, actual migrations, OTP/admin access, CLI public path, private-path denial, lock, readiness, optimized caches, and neighbouring main-site preservation. The exact downloadable tailored ZIP also passed all 50 assertions; local trusted test certificate, not the provider |
| Fresh-install ZIP | **Tailored ZIP built: 8.8 MB; ZIP integrity passed** | Locked no-dev dependencies, assets and guide; archive checks found no environments, tokens, user data, populated caches, public storage link, or `public_html` entries. Both packaged PHP public paths match `schoolshare.ethionext.com.et`; the older generic artifact is retained separately |
| `npm test` and `node tests/JavaScript/PdfPreview.test.mjs` | **5 JavaScript cases passed** | Simulated browser/library objects; npm's process runner reports one file, direct execution confirms all five cases |
| `npm run build` | Passed; Vite 8.3.2 | Builds the Vite scaffold, which current application layouts do not load through `@vite` |
| `composer validate --strict --no-check-publish` | Passed | Manifest/lockfile validation, not deployment verification |
| `npm run audit:security` | **0 vulnerabilities** | Fresh online registry audit after the locked update; does not audit hardcoded CDN assets |
| `composer audit --locked` | **No security advisories** | Current online registry results for the locked PHP graph; not proof of application security |
| Fresh locked dependency installation | Docker npm install/build and non-dev Composer installation passed | Pinned PHP/Node base images; production platform requirements pass; no new application dependency introduced |
| PHP formatting / platform | Pint passed; PHP **8.5.11** with `intl`, `pdo_mysql`, `zip`, `fileinfo`, and `mbstring` | Required extensions are installed in the shared Apache/test image; the host CLI is not the production-equivalent runtime |
| Production migration/cache compilation | All **30 migrations**, config, routes, events, and views passed on all three staging applications | Production-mode environment, debug off, persistent private/public disks and file sessions/cache; local HTTP intentionally does not prove secure-cookie/TLS behavior, covered separately by package tests |
| Monitoring fault/recovery | `/ready` returned **503** and CLI health exited **1** while the isolated MySQL service was stopped; both recovered to **200 / exit 0** after restart | Public output contains only status; scheduled checks and Docker probes are present. No remote alert recipient or backup-age monitor configured |
| CI | `.github/workflows/ci.yml` updated; YAML parses and its test/build commands pass locally | Three database targets, including the cPanel subdomain layout and SMTPS port 465, plus PHP/JS/audits; pinned actions/images; no hosted run or branch-protection configuration performed |
| Production SMTP endpoint | **Unverified:** read-only TLS handshake to `schoolshare.ethionext.com.et:465` timed out from this environment | No login or message attempted; this does not establish a provider fault. Provider authentication, certificate validation from the host, DNS/mailbox receipt and retry remain required |
| Existing development instance | Preserved; no migrations applied by this pass | Earlier read-only evidence recorded 10 pending migrations and local/debug/log-mail settings; do not confuse it with isolated staging |
| Real browser / responsive UI | Earlier evidence only | Original/remediation checks included responsive widths and PDF navigation; full current end-to-end browser acceptance was not rerun |

The October 5 diagnostic probes are historical evidence. Their failures are now
covered by normal regressions in `ProfileTest`, `AdminUserManagementTest`, and
`ReleaseDataRepairTest`. New tests reproduced blank-username and admin-page
failures before the fixes, then passed afterward. Existing application rows and
uploads were not modified; migrations were exercised only in isolated test and
staging databases. The local stack uses the sample domain `schoolshare.example`
and loopback-only HTTP/Mailpit ports. It is not a public DNS, TLS, or SMTP setup.
The additional package tests use isolated loopback HTTPS and a separate
authenticated STARTTLS/SMTPS capture services, with generated test certificates and
temporary containers/databases removed afterward. This proves the packaged
workflow locally, not production certificate issuance or mailbox delivery.

The repaired npm graph is `concurrently` 10.0.6 → `shell-quote` 1.12.0. The prior
10.0.5 → 1.9.0 graph produced two critical findings for
[GHSA-pqg4-j6r4-53mv](https://github.com/advisories/GHSA-pqg4-j6r4-53mv).
Only these two lockfile package entries changed; no dependency was added.

Installed PHP/Laravel/PHPUnit versions are 8.5.11 / 13.34.0 / 12.5.37.
The current Composer lock requires PHP 8.4.1+, despite the manifest allowing
`^8.3`. The frontend build declares Node.js 22.12+ and npm 10+.

## Security findings: current status

“Fixed” below means implemented in the repository and covered by relevant
regression checks. It does not mean every deployment has applied the required
migrations, adopted legacy blobs, or configured production infrastructure.

| Baseline ID / group | Original issue | Current implementation and status |
| --- | --- | --- |
| S01 / Group 1 | Active HTML in README Markdown | **Fixed:** raw HTML is escaped, unsafe links are disabled, and rendering inputs are bounded |
| S02 / Group 1 | Vulnerable PDF.js integration | **Fixed:** pinned PDF.js/worker 6.4.299, evaluation disabled, safe failure fallback, five JavaScript regression cases |
| S03 / Group 2 | Private projects entered My Projects search | **Fixed:** search alternatives stay grouped beneath the owner's relationship |
| S04 / Group 2 | Private checkpoint/activity metadata leaked | **Fixed:** current access scopes filter feed activity before pagination; public contributions exclude private activity |
| S05 / Group 2 | Foreign-project folder references and nested resources | **Fixed at request boundaries:** project-scoped validation/binding and guarded legacy deletion; existing invalid references require explicit audit/repair |
| S06 / Group 3 | Wrong quota owner and free retained blobs | **Fixed in code:** `stored_blobs` billing ledger, conditional reservations, reference-aware collection and reconciliation; deployed legacy data must be adopted |
| S07 / Group 4 | OTP guessing, resend budget, plaintext codes and changed-email verification | **Fixed:** shared email-bound HMAC verifier service, expiry, guess caps, shared route limits, persisted delivery budget, consumption and email-change invalidation |
| S08 / Group 2 | Unauthorized private-project starring | **Fixed:** current access is checked before mutation; star changes are transactional |
| S09 / Group 3 | Traversing ZIP entry paths | **Fixed:** validate new names and independently validate legacy paths, ancestry, cycles and collisions at export |
| S10 / Group 3 | Unbounded operations and inconsistent filesystem outcomes | **Mitigated:** bounded input/work, named throttles, lifecycle locking and failure compensation; individual stalled calls still need host timeouts |
| S11 / Group 5 | Unlocked frontend advisory chain | **Fixed:** npm lockfile, reproducible setup and Tailwind 4 Vite integration; the legacy `braces` chain is absent and the current audit is clean |

### OTP contract now implemented

`EmailOtpService` stores one keyed SHA-256 verifier per user, bound to user ID,
the current email and code. User-row transactions coordinate verification,
issuance and email changes. The plaintext code is not stored in the token
table, flashed as old input, or included in the mail subject.

- A code expires after 15 minutes and stops accepting guesses after five
  incorrect attempts; successful verification consumes it.
- Verification permits ten submissions per account and thirty per IP per ten
  minutes.
- Both resend routes share three requests per account and ten per IP per ten
  minutes. A database-backed budget additionally permits three deliveries per
  account per ten minutes, including initial and failed sends. Replacing the
  token or clearing the cache does not reset that delivery counter.
- Registration permits five attempts per IP per ten minutes.
- Email changes clear verification and old tokens before issuing a new code.
  Already-issued signed links recheck the current email under lock and consume
  outstanding tokens. Legacy notice/resend routes share the OTP workflow.
- SMTP failures preserve the account/email change and show a retry warning.
  OTP mail error reporting omits potentially sensitive transport details.

The protected-token migration intentionally invalidates pending legacy codes;
unverified users must resend after deployment. Preserve `APP_KEY`, use a real
mail transport and persistent limiter cache, and validate client-IP behavior
behind the selected proxy. See [SELF_HOSTING.md](../SELF_HOSTING.md).

Regression evidence is in `ProjectPreviewSecurityTest`, `ProjectPrivacyTest`,
`ActivityVisibilityTest`, `ProjectBoundaryTest`, `StorageLifecycleSecurityTest`,
`ResourceBoundsSecurityTest`, and `Auth/OtpSecurityTest` under `tests/Feature`.
Audits and these tests are bounded evidence, not an exhaustive penetration test
or a full secret-history review.

## Functional correctness / data integrity: current status

The original IDs are retained for traceability. “Implemented” describes the
current code, not a claim that every acceptance condition has automated or
production-engine coverage.

| ID | Current status | Remaining qualification |
| --- | --- | --- |
| F01 | Versioning conversion retains the referenced table and preserves a supporting project foreign-key index before dropping the composite index | Clean/populated upgrades pass on both target engines; an already-partial failed schema still needs its own recovery audit |
| F02 | Legacy conversion creates versions and updates real pointers without `_tmp_version_id` | Populated conversion and retained private bytes pass on both engines; `down()` does not reconstruct the original legacy layout, so use coordinated backups/forward fixes |
| F03 | Global slug uniqueness, suffix/fallback generation and creation retry implemented | Migration can rename colliding legacy slugs; verify existing links and concurrency on the deployment engine |
| F04 | Checkpoint pages load the actual version/snapshot relationships | Relevant checkpoint/access regression cases pass |
| F05 | Bounded text diff uses the installed API and escapes output | Office diffing is intentionally disabled, not implemented |
| F06 | Comments bind beneath their project and enforce access/deletion permissions | Scoped comment regression cases pass |
| F07 | Collaborator forms pass model IDs consistently | Numeric usernames and foreign-member management are covered |
| F08 | **Fixed in code:** blank saves retain route identities; missing identities are generated/backfilled | Backfill must be migrated; blank legacy names, collisions, reader pages, and generated-name length are covered; see R01 |
| F09 | Explore searches the current tag relationship | Relevant regressions pass on both MySQL-family engines; representative query/load profiling remains separate |
| F10 | Project/account lifecycle cleanup and recoverable logical-file deletion implemented | Folder/file history retention is deliberate; final physical collection is reference-aware and may require cleanup retry |
| F11 | Complete immutable checkpoint manifests and restore implemented; snapshot unique indexes use portable short names | Real HTTP rename/move/delete/restore and post-backup restoration pass on both engines; legacy checkpoints without manifests fail safely |
| F12 | Reconciliation uses private version/blob paths and correct billing ownership | Reconciliation and destructive `--cleanup` require coordinated backup and deployment review |
| F13 | **Fixed in code:** populated admin pages use the application formatter and correct plan-edit route | List/detail/dashboard and authorized/unauthorized/invalid plan changes pass without `intl`; see R02 |
| F14 | Canonical `free`/`pro`/`custom` plans, corrected admin user-management labels, and legacy `student` conversion implemented | Stored billing-cycle labels are not recurring billing |
| F15 | Version sequence allocation and immutable per-version MIME metadata implemented | Broad concurrent production-engine verification remains outstanding |
| F16 | Historical links select the recorded version; documentation narrowed to current-project ZIP export | A historical checkpoint ZIP remains out of scope, not silently “completed” |
| F17 | Shared edit/access helpers align owner/editor/viewer operations and UI decisions | Continue expanding role-matrix coverage for remaining screens; editors may delete project content/history under the current contract |
| F18 | Upgrade routes, retryable mail, account-locked pending creation, and pending-only approve/reject transitions implemented | Independent-session parallel HTTP submissions produce one pending request on both engines; broader load is untested; automatic subscriptions/expiry/renewal are not implemented; see R04 |
| F19 | Project/collaborator limits, operation bounds and transactional star/follow mutations implemented | Some legacy config keys remain unused, including cache TTLs and the separate global storage setting; avoid presenting them as enforced controls |
| F20 | Non-null root scopes and uniqueness constraints implemented with database-collation-aware deduplication | Populated case/accent/trailing-space and existing-suffix collisions pass on both engines; apply repairs under a reviewed migration/backup procedure |
| F21 | Account/invitation/upgrade changes survive mail failure with retry paths; specified reports and stored upgrade errors are redacted | Actual captured SMTP/451 failures and retries pass on both engines; real provider/TLS/mailbox receipt remains unverified; protect old logs/backups; see R05 |
| F22 | Contributor deletion preserves foreign-project checkpoints with anonymized attribution | The committed history-preservation regression passes; billing ownership can intentionally block account deletion while foreign live blobs remain charged |

The two original registration/profile test failures are fixed. There are no
failures in the current repository or opt-in staging runs. This remains bounded
coverage, not proof of every concurrent, load, provider, or browser behavior.

Production portability repairs are in the historical pre-release migrations:
supporting indexes precede foreign-key-related index drops (including OTP
rollback), snapshot unique indexes stay below engine identifier limits, and
legacy-name comparisons use the database's actual collation. A later migration
cannot fix an earlier migration that fails before reaching it. Already-applied
migrations are not rerun automatically; do not blindly retry a partially failed
deployment or use `migrate:fresh` against real application data.

## Release repairs and remaining priorities

### R01 — P1: missing usernames — repaired in code

**Historical defect:** a blank profile update cleared the username and broke
project/profile URL rendering. `User::saving` now preserves a filled original
username, or generates one when the account has no identity. Valid changes to
an available username still work. Generated names and collision suffixes fit
the profile's 50-character validation limit.

`2026_10_11_104126_backfill_missing_usernames` repairs existing null, empty, and
space-only values in chunks using deterministic `user_{id}` names and collision
suffixes. It leaves filled identities alone and is repeatable; rollback retains
the assigned identities. `ProfileTest` and `ReleaseDataRepairTest` cover blank
updates, duplicate rejection, long-name collisions, legacy saves/backfill,
reruns, chunk boundaries, and project/profile/explore/feed page rendering.
These regressions and the migration chain now pass on both staging engines;
application to the existing development or production database remains pending.

### R02 — P1: populated admin pages — repaired in code

**Historical defect:** populated admin pages called `Number::fileSize`, which
requires the locally absent `intl` extension. They now reuse `User`'s existing
storage formatter, so `intl` is optional for these pages. Verification also
found and repaired the user-list form's nonexistent `admin.users.update-plan`
route; the form now uses the actual `admin.users.updatePlan` route.

`AdminUserManagementTest` verifies populated list/detail/dashboard rendering
without `intl`, correct form links, all supported plan changes, invalid-plan
rejection, and denied non-admin access. User-management plan labels now show
Pro and Custom without conflating Custom with yearly billing. Hosting guides
explicitly name the required database driver. The production-equivalent runtime
installs `intl` even though this formatter does not require it.

### R03 — P2: combined admin filters — repaired in code

**Historical defect:** the ungrouped name/email OR allowed a name match to bypass
the selected plan. `Admin/UserController::index` now groups the alternatives
before applying the plan filter. A rendered-page/view-data regression includes
both name and email matches on Pro, a matching Free account, and an unrelated
Pro account; only the two matching Pro accounts are returned.

### R04 — P2: pending upgrade creation — account lock implemented

**Historical risk:** pending-request lookup occurred outside the creation
transaction, allowing two requests to observe no pending row. Creation now
locks the account first inside the transaction, then performs a locking lookup
and creates only if no pending request exists for that account/plan. Laravel
retries concurrency failures up to three transaction attempts. Mail delivery
runs after the transaction commits.

`UpgradeRequestTest` verifies repeat submission reuse, preservation of the
original billing cycle, creation after a rejected request, input validation,
and unverified-account rejection. `MailDeliverySecurityTest` verifies failed
deliveries retry the same row. Both suites pass on SQLite and the two target
engines. `StagingAcceptanceTest` additionally sends simultaneous HTTP requests
from independent authenticated sessions to Apache: MySQL and MariaDB each
retain one pending row, preserve its original billing cycle, and deliver captured
mail. This closes the targeted race check, not general concurrency/load testing.

### R05 — P2: OTP/invitation/upgrade mail redaction — repaired in code

`MailDeliveryFailure` standardizes the specified OTP, collaborator-invitation,
and upgrade-instruction failure reports. It retains only the operation and
exception class, with no raw transport message or previous exception. Upgrade
requests store that safe message, while delivery status and retry behavior are
preserved. `2026_10_11_104127_redact_upgrade_mail_delivery_errors` replaces
previously stored non-null errors with a generic retry diagnostic; rollback
does not restore the original messages.

OTP regressions and `MailDeliverySecurityTest` exercise synthetic sensitive
transport messages, absence of raw reports/exception chains, failed buyer and
admin emails, saved collaborator/request state, and successful retries.
`ReleaseDataRepairTest` covers legacy stored-error redaction without changing
request status. Apply the migration and restrict historical logs/backups; it
cannot erase messages already copied there. The real SMTP protocol is exercised
against isolated Mailpit, including forced 451 failures and successful OTP,
invitation, and upgrade retries. Real-provider credentials/TLS and mailbox
delivery remain an R06 gate.

### R06 — release gate: local staging passed; public operations pending

Requested deployment sequence status:

| Step | Status / next gate |
| --- | --- |
| 3 — Provision staging | **Passed locally:** PHP 8.5.11, MySQL 8.4.11, MariaDB 11.4.13, upstream MariaDB 10.11.19, required extensions, isolated secrets/data, Apache, and SMTP capture; provider CloudLinux variant still needs host acceptance |
| 4 — Migrations and recovery | **Passed locally:** clean and populated migration chains, private bytes/ledger checks, database dump/import plus private-file archive restoration and checkpoint recovery |
| 5 — Application acceptance | **Passed locally:** actual HTTP/SMTP OTP, failures/retries, permissions, uploads/avatar, ZIP, checkpoint restoration, and parallel upgrade requests; real HTTPS/provider delivery still pending |
| 6 — CI and monitoring | **Implemented:** pinned CI matrix, readiness probes, scheduled health checks, and tested database outage/recovery; hosted CI, external uptime/log/disk alerts, and backup-age checks still pending |
| 7 — Public deployment | **Held:** do not deploy until real-host operational acceptance passes |

Before a public production release:

1. Confirm the actual cPanel hosting account's capabilities and one production database
   engine/version. Replace `schoolshare.example` with the real domain; verify
   DNS, HTTPS, secure sessions, reverse-proxy client IPs, and request/body limits.
2. Configure the SMTP provider and sender domain without exposing credentials.
   Verify authentication/TLS, mailbox receipt, and failure/retry with the actual
   provider. Mailpit is not deliverability evidence.
3. Establish coordinated, retained off-host database/private-file backups and
   prove restoration on the selected host. Quiesce all writers during capture;
   a transactional dump alone does not coordinate filesystem changes. Review
   cleanup targets before destructive reconciliation; pending legacy OTPs are
   invalidated by the migration.
4. Enable external uptime/readiness, safe error/disk alerts, and backup-age
   monitoring with tested alert delivery. Container health status and stderr
   logging alone do not notify an operator or restart unhealthy containers.
5. Run the new CI on the hosting repository and require its checks before
   release. No commit, push, hosted workflow run, or branch-policy change was
   performed here. Add a full rendered-browser smoke workflow where needed.
6. Exercise broader concurrent quota/identity behavior and representative load
   on the selected host. Staging supplies PHP body/execution/socket bounds;
   validate real proxy, database, filesystem, and SMTP timeout behavior too.

Reproduction commands and safe staging-only credentials/database boundaries are
in [SELF_HOSTING.md](../SELF_HOSTING.md#repeatable-isolated-staging). The local
stack remains running on loopback; it is not configured for public exposure.

### R07 — standard cPanel browser installation: implemented and verified locally

The chosen standard installation requires the owner to create a dedicated empty
database/user in cPanel. The application does not provision database servers or
request root credentials. A release ZIP extracts to sibling private `schoolshare`
and public `public_html` directories; Composer/npm are not required on the host.

First launch generates a private application key and random owner token. The
wizard requires HTTPS, CSRF, rate-limited token unlock, a short-lived authorized
session, compatible PHP/extensions/engine, writable private storage, and an
empty selected database. It writes restricted production configuration, runs
migrations, creates exactly one chosen admin, uses the existing OTP flow, and
permanently disables setup. No demo users or shared credentials are included.
Secrets are not flashed or redisplayed; environment values cannot inject new
settings or interpolate other variables, and password whitespace is preserved.
Upgrade notifications are configured for the newly chosen administrator.

Existing tables/views and occupied public-storage paths are preserved. Failed
migrations/timeouts leave a recovery marker and refuse blind retries. Failed
SMTP delivery preserves the admin and permanent lock, allowing configuration
repair and OTP resend without reinstalling. Missing installed configuration
must be restored with its original APP_KEY, not regenerated.

Earlier generic artifact (not suitable for this existing subdomain layout):
`storage/app/releases/schoolshare-cpanel-2026-10-11.zip`.
SHA-256: `a01df1acfee898a2b6e2cfde93a0228246493ec8baf96f97230d48e791377597`.
The artifact is intentionally ignored by Git. Build/test vendor copies, test
certificates, and disposable package instances were removed; the release ZIP
was retained. Installation and maintainer packaging instructions are in
[INSTALLATION_CPANEL.md](INSTALLATION_CPANEL.md). This is a fresh-install path,
not an existing-site updater, and does not bypass the R06 production gates.

### R08 — actual cPanel target and subdomain layout: locally verified, host gate pending

The supplied hosting screenshots report PHP 8.5 as the account default and
`10.11.19-MariaDB-cll-lve` as the database server. phpMyAdmin's own PHP 8.4.26
does not override the domain runtime; PostgreSQL 13.23 is not the installer target.
All four domains share the PHP Selector default, and the host has disabled its
per-domain isolation controls. Existing sites must be smoke-checked after the
account-level PHP change, and required PHP 8.5 extensions must be verified.

Added a digest-pinned upstream MariaDB 10.11.19 service and separate app/data
volumes without replacing MariaDB 11.4 or changing development/production data.
Clean/populated migrations, 277 ordinary regressions, coordinated dump/restore,
all nine installer cases, and four production HTTP acceptance cases pass on this
target. An initial overlapping test-database run caused fixture table conflicts;
both affected jobs were rerun sequentially and passed. This was a test-runner
collision, not a production-engine defect. Local tests do not reproduce the
provider's exact CloudLinux build, disabled-function policy, or hosting limits.

The installer now accepts MariaDB 10.11.19+ only within the 10.11 release line,
alongside the original MySQL 8.4/MariaDB 11.4 lines. Version-policy regressions
cover the provider suffix, compatibility prefix, minimum patch, unsupported
lines, malformed versions, and wrong driver. No dependency changes were needed.

`schoolshare:package --public-directory=schoolshare.ethionext.com.et` validates
a single safe public-folder name and adjusts both the public front controller
and Laravel's web/CLI public path. Unsafe/private folder names and unexpected
bootstrap layouts are refused; partial failed archives are removed. The tailored
ZIP contains private `schoolshare` and public `schoolshare.ethionext.com.et`
siblings, with no `public_html` entries. Do not rename folders or extract it
inside a website root. Use only confirmed empty destinations and a new database.

The exact generated release was extracted and installed over local HTTPS with
actual CSRF and secure sessions, upstream MariaDB 10.11.19, and authenticated
SMTPS/implicit TLS on port 465. Admin OTP receipt/verification, permanent lock,
private-path denial, correct CLI public path, readiness and optimized caches
pass; the neighbouring main-site sentinel remained unchanged. Generic packages
also pass on MySQL 8.4 and MariaDB 11.4 with authenticated STARTTLS.

Current tailored artifact:
`storage/app/releases/schoolshare-ethionext-cpanel-mariadb-10.11.19-2026-10-11.zip`.
SHA-256: `4b856da10709d7eda64a322326b89f0ef4099182c3eedb6053d8ed11a446d1c4`.
The 8.8 MB ZIP passed integrity and secret/user-data/main-root exclusion checks.
Temporary vendor copies and package-test containers/databases were removed;
both release archives are retained and ignored by Git.

The updated [installation guide](INSTALLATION_CPANEL.md) gives the correct layout,
database setup, private owner-token retrieval, and supplied sender settings:
`schoolshare.ethionext.com.et`, SMTPS port 465, and
`notifications@schoolshare.ethionext.com.et`. No passwords or production keys
are bundled. The read-only real SMTP connection probe timed out; do not treat
local capture as real delivery evidence. Actual-host TLS/extensions/permissions,
provider mailbox delivery, cron, off-host restoration, external alerts, hosted
CI and rendered-browser acceptance remain required. No files or settings were
changed on the hosting account, and no public deployment was performed.

## Architecture: what improved and what to modify next

Keep the Laravel monolith. `StorageLifecycle`, `CheckpointSnapshotService`,
`ArchivePath` and `EmailOtpService` now give the important invariants clearer
owners; microservices or a SPA are not prerequisites for correctness.

The current design intentionally supports one application host and private
local storage. Its OS file lock is not shared across independent replicas or
deployment directories. Add shared storage and coordinated locking before
horizontal scaling; do not treat a second web node as a configuration-only
change.

Maintain one permission contract across controllers and templates. The
project access/edit helpers and scoped routes are a useful foundation; expand
the role matrix as screens change. Extract further operations only where they
remove actual duplication or make an invariant independently testable.

Measure query counts and representative request costs before adding indexes or
services. Some shared-project/folder lists and view relationship lookups still
warrant profiling. Move large exports or durable mail delivery to bounded jobs
only when workers and retries can be operated reliably; current work remains
synchronous and deliberately bounded.

The frontend remains split: live layouts use Bootstrap/CDN assets while a
separate Tailwind 4/Vite/Alpine scaffold builds successfully. Choose one delivery
strategy when justified. The npm lockfile does not cover Bootstrap, PDF.js or
CodeMirror CDN URLs; retain an external-asset inventory and review CDN
availability, integrity and privacy separately.

Office preview through Google Docs Viewer cannot retrieve authenticated file
URLs. Keep download fallback and disabled Office diffing explicit; do not expose
private files to make the external preview work. Current-project ZIP export is
implemented; historical checkpoint ZIPs are not.

## Documentation and product-contract status

| Document | Current decision / remaining work |
| --- | --- |
| `README.md` | Updated verified PHP/database targets, extension requirements, and staging/runbook link; distinguishes local acceptance from public readiness |
| `SELF_HOSTING.md` | Includes production SMTP/session/driver/storage template, portable-migration caveats, reproducible engine/recovery/HTTP acceptance, CI, monitoring, and the remaining real-host gates |
| `docs/ARCHITECTURE.md` | Keep; reflects the ledger, snapshots, OTP service, access rules and single-host limits |
| `docs/API.md` | Keep as a web-route reference, not a REST specification; add the implemented `GET /upgrades/{plan}` and `POST /upgrades` user routes |
| `SECURITY.md` | Keep; reports current controls and deployment boundaries without claiming absolute security |
| `CHANGELOG.md`, `CONTRIBUTING.md` | Keep; record all five security groups and current verification/reproducible install commands |
| `ROADMAP.md`, `PRODUCTION_GUIDE.md` | Retired; do not recreate duplicate roadmap/deployment guidance |
| `LICENSE.md` | Keep, but the copyright holder must resolve its SCL terms versus Composer's MIT metadata |
| `CODE_OF_CONDUCT.md` | Useful for a public contributor community; optional for a closed solo project |
| `AGENTS.md`, `CLAUDE.md`, generated skills/MCP files | Tooling, not runtime product requirements; keep common policy aligned and local/generated material separate from shipped code |

Remaining product/documentation mismatches include the guest layout's “Open
Source” label alongside the restrictive SCL text and promised ZIP attribution
not inserted by the
current exporter. The application checks only an `SS-` license-key prefix;
it does not implement commercial entitlement validation. These are explicit
limitations, not validated licensing enforcement or subscription automation.

Extend existing guides rather than adding speculative documents. The useful
missing detail is a supported-engine release checklist, admin provisioning and
recovery procedure, and a deliberate privacy/retention explanation covering
public/private content, collaboration and external CDN/viewer requests. Third-
party notices must match redistributed assets. Legal/product choices belong to
the maintainer; this reassessment does not choose the source-license model.

## Historical engineering rating — 5 October 2026

The following table preserves the October 5 assessment and its then-current
reasoning for comparison with the original baseline. It is not the current
issue list: R01–R05 repairs and current staging results are recorded above. The
October 11 pass did not rerate architecture, interface, or production operations.

| Area | Score / 10 | Weight | October 5 reasoning (historical) |
| --- | --- | --- | --- |
| Security | 8 | 20% | Original groups remediated with targeted tests and clean locked audits; CDN, deployment and non-OTP error-redaction checks remain |
| Functional correctness / data integrity | 7.5 | 25% | Stronger snapshots, version metadata, ownership and failure handling; residual identity/admin defects and upgrade creation race prevent a higher score |
| Architecture / maintainability | 7.5 | 15% | Appropriate monolith with clearer invariant ownership; controller/query consistency, split frontend and single-host coordination remain constraints |
| Testing / verification | 7.5 | 15% | 181 passing PHP cases and five JavaScript cases now cover meaningful boundaries; uncovered admin/identity cases, no CI/browser automation, and no production-engine/concurrent acceptance |
| Deployment / operations | 6 | 10% | Repaired code and consolidated runbook, but target-engine upgrade, extension/mail/proxy setup and backup recovery still unproven |
| Documentation accuracy | 8 | 10% | Useful current architecture/security/operations guides; remaining extension/SMTP/routes/product-copy inconsistencies are explicitly identified |
| Interface / product clarity | 8 | 5% | Coherent concept and earlier responsive/browser evidence; current full browser acceptance and plan/preview clarity still need work |

Weighted result: **7.525/10**, rounded overall rating: **7.5/10**.
The original weighted result was 3.95/10, rounded to 4/10.

The next release gains are hosted CI enforcement and actual-host HTTPS, provider
SMTP, off-host recovery, external alerts, representative load, and complete
browser acceptance. Local staging closes the targeted migration, restore, and
upgrade-race checks; it does not substitute for those operational checks.

## Baseline and history

At `c6bf19b`, the review recorded reproduced browser execution/private metadata
leaks, unsafe ZIP entries, broken core feature paths, migration failures and two
failures in the original 25-test suite. Those were historical findings, not the
results of today's repository/staging runs. Subsequent remediation is recorded in
[CHANGELOG.md](../CHANGELOG.md) and Git history; the previous detailed report is
available in the report file at commit `b9bc3a7`.

The October 11 updates include application/lockfile repairs, two data migrations,
portable historical migrations, engine/HTTP/recovery regressions, CI/monitoring,
the owner-locked fresh-install wizard, tested cPanel packaging, and corrected
production/installation documentation. They supersede the
October 5 present-tense R01–R05 findings while preserving their IDs and historical
rating. No public deployment, existing-development-database migration, or
production cleanup was performed. Recovery/concurrency drills were confined to
the isolated local staging stack, with no provider mail or off-host backups.
